<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\PDO;

use PDO;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionPDOInterface;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Oracle\ColumnSchema;
use Yiisoft\Db\Oracle\ColumnSchemaBuilder;
use Yiisoft\Db\Oracle\TableSchema;
use Yiisoft\Db\Query\QueryBuilder;
use Yiisoft\Db\Schema\Schema;

/**
 * Schema is the class for retrieving metadata from an Oracle database.
 *
 * @property string $lastInsertID The row ID of the last row inserted, or the last value retrieved from the
 * sequence object. This property is read-only.
 *
 * @psalm-type ConstraintArray = array<
 *   array-key,
 *   array {
 *     name: string,
 *     column_name: string,
 *     type: string,
 *     foreign_table_schema: string|null,
 *     foreign_table_name: string|null,
 *     foreign_column_name: string|null,
 *     on_update: string,
 *     on_delete: string,
 *     check_expr: string
 *   }
 * >
 */
final class SchemaPDOOracle extends Schema
{
    /**
     * @var array map of DB errors and corresponding exceptions.
     *
     * If left part is found in DB error message exception class from the right part is used.
     */
    protected array $exceptionMap = [
        'ORA-00001: unique constraint' => IntegrityException::class,
    ];

    public function __construct(private ConnectionPDOInterface $db, SchemaCache $schemaCache)
    {
        $this->defaultSchema = strtoupper($db->getDriver()->getUsername());
        parent::__construct($schemaCache);
    }

    protected function resolveTableName(string $name): TableSchema
    {
        $resolvedName = new TableSchema();

        $parts = explode('.', str_replace('"', '', $name));

        if (isset($parts[1])) {
            $resolvedName->schemaName($parts[0]);
            $resolvedName->name($parts[1]);
        } else {
            $resolvedName->schemaName($this->defaultSchema);
            $resolvedName->name($name);
        }

        $fullName = ($resolvedName->getSchemaName() !== $this->defaultSchema
            ? $resolvedName->getSchemaName() . '.' : '') . $resolvedName->getName();

        $resolvedName->fullName($fullName);

        return $resolvedName;
    }

    /**
     * @see https://docs.oracle.com/cd/B28359_01/server.111/b28337/tdpsg_user_accounts.htm
     */
    protected function findSchemaNames(): array
    {
        $sql = <<<SQL
        SELECT "u"."USERNAME"
        FROM "DBA_USERS" "u"
        WHERE "u"."DEFAULT_TABLESPACE" NOT IN ('SYSTEM', 'SYSAUX')
        ORDER BY "u"."USERNAME" ASC
        SQL;

        return $this->db->createCommand($sql)->queryColumn();
    }

    /**
     * @param string $schema
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array
     */
    protected function findTableNames(string $schema = ''): array
    {
        if ($schema === '') {
            $sql = <<<SQL
            SELECT TABLE_NAME
            FROM USER_TABLES
            UNION ALL
            SELECT VIEW_NAME AS TABLE_NAME
            FROM USER_VIEWS
            UNION ALL
            SELECT MVIEW_NAME AS TABLE_NAME
            FROM USER_MVIEWS
            ORDER BY TABLE_NAME
            SQL;

            $command = $this->db->createCommand($sql);
        } else {
            $sql = <<<SQL
            SELECT OBJECT_NAME AS TABLE_NAME
            FROM ALL_OBJECTS
            WHERE OBJECT_TYPE IN ('TABLE', 'VIEW', 'MATERIALIZED VIEW') AND OWNER = :schema
            ORDER BY OBJECT_NAME
            SQL;
            $command = $this->db->createCommand($sql, [':schema' => $schema]);
        }

        $rows = $command->queryAll();
        $names = [];

        foreach ($rows as $row) {
            if ($this->db->getSlavePdo()->getAttribute(PDO::ATTR_CASE) === PDO::CASE_LOWER) {
                $row = array_change_key_case($row, CASE_UPPER);
            }
            $names[] = $row['TABLE_NAME'];
        }

        return $names;
    }

    /**
     * @param string $name
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return TableSchema|null
     */
    protected function loadTableSchema(string $name): ?TableSchema
    {
        $table = new TableSchema();

        $this->resolveTableNames($table, $name);

        if ($this->findColumns($table)) {
            $this->findConstraints($table);
            return $table;
        }

        return null;
    }

    /**
     * @param string $tableName
     *
     * @throws Exception|InvalidConfigException|NotSupportedException|Throwable
     *
     * @return Constraint|null
     */
    protected function loadTablePrimaryKey(string $tableName): ?Constraint
    {
        return $this->loadTableConstraints($tableName, 'primaryKey');
    }

    /**
     * @param string $tableName
     *
     * @throws Exception|InvalidConfigException|NotSupportedException|Throwable
     *
     * @return array
     */
    protected function loadTableForeignKeys(string $tableName): array
    {
        return $this->loadTableConstraints($tableName, 'foreignKeys');
    }

    /**
     * @param string $tableName
     *
     * @throws Exception|InvalidConfigException|NotSupportedException|Throwable
     *
     * @return array
     */
    protected function loadTableIndexes(string $tableName): array
    {
        $sql = <<<SQL
        SELECT "ui"."INDEX_NAME" AS "name", "uicol"."COLUMN_NAME" AS "column_name",
        CASE "ui"."UNIQUENESS" WHEN 'UNIQUE' THEN 1 ELSE 0 END AS "index_is_unique",
        CASE WHEN "uc"."CONSTRAINT_NAME" IS NOT NULL THEN 1 ELSE 0 END AS "index_is_primary"
        FROM "SYS"."USER_INDEXES" "ui"
        LEFT JOIN "SYS"."USER_IND_COLUMNS" "uicol"
        ON "uicol"."INDEX_NAME" = "ui"."INDEX_NAME"
        LEFT JOIN "SYS"."USER_CONSTRAINTS" "uc"
        ON "uc"."OWNER" = "ui"."TABLE_OWNER" AND "uc"."CONSTRAINT_NAME" = "ui"."INDEX_NAME" AND "uc"."CONSTRAINT_TYPE" = 'P'
        WHERE "ui"."TABLE_OWNER" = :schemaName AND "ui"."TABLE_NAME" = :tableName
        ORDER BY "uicol"."COLUMN_POSITION" ASC
        SQL;

        $resolvedName = $this->resolveTableName($tableName);

        $indexes = $this->db->createCommand($sql, [
            ':schemaName' => $resolvedName->getSchemaName(),
            ':tableName' => $resolvedName->getName(),
        ])->queryAll();

        $indexes = $this->normalizePdoRowKeyCase($indexes, true);

        $indexes = ArrayHelper::index($indexes, null, 'name');

        $result = [];

        /**
         * @psalm-var object|string|null $name
         * @psalm-var array[] $index
         */
        foreach ($indexes as $name => $index) {
            $columnNames = ArrayHelper::getColumn($index, 'column_name');

            if ($columnNames[0] === null) {
                $columnNames[0] = '';
            }

            $result[] = (new IndexConstraint())
                ->primary((bool) $index[0]['index_is_primary'])
                ->unique((bool) $index[0]['index_is_unique'])
                ->name($name)
                ->columnNames($columnNames);
        }

        return $result;
    }

    /**
     * @param string $tableName
     *
     * @throws Exception|InvalidConfigException|NotSupportedException|Throwable
     *
     * @return array
     */
    protected function loadTableUniques(string $tableName): array
    {
        return $this->loadTableConstraints($tableName, 'uniques');
    }

    /**
     * @param string $tableName
     *
     * @throws Exception|InvalidConfigException|NotSupportedException|Throwable
     *
     * @return array
     */
    protected function loadTableChecks(string $tableName): array
    {
        return $this->loadTableConstraints($tableName, 'checks');
    }

    /**
     * @param string $tableName
     *
     * @throws NotSupportedException if this method is called.
     *
     * @return array
     */
    protected function loadTableDefaultValues(string $tableName): array
    {
        throw new NotSupportedException('Oracle does not support default value constraints.');
    }

    public function releaseSavepoint(string $name): void
    {
        /* does nothing as Oracle does not support this */
    }

    public function quoteSimpleTableName(string $name): string
    {
        return str_contains($name, '"') ? $name : '"' . $name . '"';
    }

    /**
     * Create a column schema builder instance giving the type and value precision.
     *
     * This method may be overridden by child classes to create a DBMS-specific column schema builder.
     *
     * @param string $type type of the column. See {@see ColumnSchemaBuilder::$type}.
     * @param array|int|string|null $length length or precision of the column {@see ColumnSchemaBuilder::$length}.
     *
     * @return ColumnSchemaBuilder column schema builder instance
     */
    public function createColumnSchemaBuilder(string $type, array|int|string $length = null): ColumnSchemaBuilder
    {
        return new ColumnSchemaBuilder($type, $length);
    }

    /**
     * Resolves the table name and schema name (if any).
     *
     * @param TableSchema $table the table metadata object
     * @param string $name the table name
     */
    protected function resolveTableNames(TableSchema $table, string $name): void
    {
        $parts = explode('.', str_replace('"', '', $name));

        if (isset($parts[1])) {
            $table->schemaName($parts[0]);
            $table->name($parts[1]);
        } else {
            $table->schemaName($this->defaultSchema);
            $table->name($name);
        }

        $table->fullName($table->getSchemaName() !== $this->defaultSchema
            ? $table->getSchemaName() . '.' . $table->getName() : $table->getName());
    }

    /**
     * Collects the table column metadata.
     *
     * @param TableSchema $table the table schema.
     *
     * @throws Exception|Throwable
     *
     * @return bool whether the table exists.
     */
    protected function findColumns(TableSchema $table): bool
    {
        $sql = <<<'SQL'
SELECT
    A.COLUMN_NAME,
    A.DATA_TYPE,
    A.DATA_PRECISION,
    A.DATA_SCALE,
    (
      CASE A.CHAR_USED WHEN 'C' THEN A.CHAR_LENGTH
        ELSE A.DATA_LENGTH
      END
    ) AS DATA_LENGTH,
    A.NULLABLE,
    A.DATA_DEFAULT,
    COM.COMMENTS AS COLUMN_COMMENT
FROM ALL_TAB_COLUMNS A
    INNER JOIN ALL_OBJECTS B ON B.OWNER = A.OWNER AND LTRIM(B.OBJECT_NAME) = LTRIM(A.TABLE_NAME)
    LEFT JOIN ALL_COL_COMMENTS COM ON (A.OWNER = COM.OWNER AND A.TABLE_NAME = COM.TABLE_NAME AND A.COLUMN_NAME = COM.COLUMN_NAME)
WHERE
    A.OWNER = :schemaName
    AND B.OBJECT_TYPE IN ('TABLE', 'VIEW', 'MATERIALIZED VIEW')
    AND B.OBJECT_NAME = :tableName
ORDER BY A.COLUMN_ID
SQL;

        try {
            $columns = $this->db->createCommand($sql, [
                ':tableName' => $table->getName(),
                ':schemaName' => $table->getSchemaName(),
            ])->queryAll();
        } catch (Exception $e) {
            return false;
        }

        if (empty($columns)) {
            return false;
        }

        foreach ($columns as $column) {
            if ($this->db->getSlavePdo()->getAttribute(PDO::ATTR_CASE) === PDO::CASE_LOWER) {
                $column = array_change_key_case($column, CASE_UPPER);
            }

            $c = $this->createColumn($column);

            $table->columns($c->getName(), $c);
        }

        return true;
    }

    /**
     * Sequence name of table.
     *
     * @param string $tableName
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return int|null|string whether the sequence exists.
     *
     * @internal TableSchema `$table->getName()` the table schema.
     */
    protected function getTableSequenceName(string $tableName): string|int|null
    {
        $sequenceNameSql = <<<SQL
        SELECT
            UD.REFERENCED_NAME AS SEQUENCE_NAME
        FROM USER_DEPENDENCIES UD
            JOIN USER_TRIGGERS UT ON (UT.TRIGGER_NAME = UD.NAME)
        WHERE
            UT.TABLE_NAME = :tableName
            AND UD.TYPE = 'TRIGGER'
            AND UD.REFERENCED_TYPE = 'SEQUENCE'
        SQL;
        $sequenceName = $this->db->createCommand($sequenceNameSql, [':tableName' => $tableName])->queryScalar();

        return $sequenceName === false ? null : $sequenceName;
    }

    /**
     * @Overrides method in class 'Schema'
     *
     * {@see https://secure.php.net/manual/en/function.PDO-lastInsertId.php} -> Oracle does not support this.
     *
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string $sequenceName name of the sequence object (required by some DBMS)
     *
     * @throws Exception|InvalidCallException|InvalidConfigException|Throwable if the DB connection is not active.
     *
     * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object.
     */
    public function getLastInsertID(string $sequenceName = ''): string
    {
        if ($this->db->isActive()) {
            /* get the last insert id from the master connection */
            $sequenceName = $this->quoteSimpleTableName($sequenceName);

            return $this->db->useMaster(function (ConnectionPDOInterface $db) use ($sequenceName) {
                return $db->createCommand("SELECT $sequenceName.CURRVAL FROM DUAL")->queryScalar();
            });
        }

        throw new InvalidCallException('DB Connection is not active.');
    }

    /**
     * Creates ColumnSchema instance.
     *
     * @param array|string $column
     *
     * @return ColumnSchema
     */
    protected function createColumn(array|string $column): ColumnSchema
    {
        $c = $this->createColumnSchema();

        $c->name($column['COLUMN_NAME']);
        $c->allowNull($column['NULLABLE'] === 'Y');
        $c->comment($column['COLUMN_COMMENT'] ?? '');
        $c->primaryKey(false);

        $this->extractColumnType(
            $c,
            $column['DATA_TYPE'],
            $column['DATA_PRECISION'],
            $column['DATA_SCALE'],
            $column['DATA_LENGTH']
        );

        $this->extractColumnSize(
            $c,
            $column['DATA_TYPE'],
            $column['DATA_PRECISION'],
            $column['DATA_SCALE'],
            $column['DATA_LENGTH']
        );

        $c->phpType($this->getColumnPhpType($c));

        if (!$c->isPrimaryKey()) {
            if ($column['DATA_DEFAULT'] !== null && stripos($column['DATA_DEFAULT'], 'timestamp') !== false) {
                $c->defaultValue(null);
            } else {
                $defaultValue = $column['DATA_DEFAULT'];

                if ($c->getType() === 'timestamp' && $defaultValue === 'CURRENT_TIMESTAMP') {
                    $c->defaultValue(new Expression('CURRENT_TIMESTAMP'));
                } else {
                    if ($defaultValue !== null) {
                        if (($len = strlen($defaultValue)) > 2 && $defaultValue[0] === "'"
                            && $defaultValue[$len - 1] === "'"
                        ) {
                            $defaultValue = substr($column['DATA_DEFAULT'], 1, -1);
                        } else {
                            $defaultValue = trim($defaultValue);
                        }
                    }
                    $c->defaultValue($c->phpTypecast($defaultValue));
                }
            }
        }

        return $c;
    }

    /**
     * Finds constraints and fills them into TableSchema object passed.
     *
     * @param TableSchema $table
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    protected function findConstraints(TableSchema $table): void
    {
        $sql = <<<'SQL'
SELECT
    /*+ PUSH_PRED(C) PUSH_PRED(D) PUSH_PRED(E) */
    D.CONSTRAINT_NAME,
    D.CONSTRAINT_TYPE,
    C.COLUMN_NAME,
    C.POSITION,
    D.R_CONSTRAINT_NAME,
    E.TABLE_NAME AS TABLE_REF,
    F.COLUMN_NAME AS COLUMN_REF,
    C.TABLE_NAME
FROM ALL_CONS_COLUMNS C
    INNER JOIN ALL_CONSTRAINTS D ON D.OWNER = C.OWNER AND D.CONSTRAINT_NAME = C.CONSTRAINT_NAME
    LEFT JOIN ALL_CONSTRAINTS E ON E.OWNER = D.R_OWNER AND E.CONSTRAINT_NAME = D.R_CONSTRAINT_NAME
    LEFT JOIN ALL_CONS_COLUMNS F ON F.OWNER = E.OWNER AND F.CONSTRAINT_NAME = E.CONSTRAINT_NAME AND F.POSITION = C.POSITION
WHERE
    C.OWNER = :schemaName
    AND C.TABLE_NAME = :tableName
ORDER BY D.CONSTRAINT_NAME, C.POSITION
SQL;

        $command = $this->db->createCommand($sql, [
            ':tableName' => $table->getName(),
            ':schemaName' => $table->getSchemaName(),
        ]);

        $constraints = [];

        foreach ($command->queryAll() as $row) {
            if ($this->db->getSlavePdo()->getAttribute(PDO::ATTR_CASE) === PDO::CASE_LOWER) {
                $row = array_change_key_case($row, CASE_UPPER);
            }

            if ($row['CONSTRAINT_TYPE'] === 'P') {
                $table->getColumns()[$row['COLUMN_NAME']]->primaryKey(true);
                $table->primaryKey($row['COLUMN_NAME']);

                if (empty($table->getSequenceName())) {
                    $table->sequenceName($this->getTableSequenceName($table->getName()));
                }
            }

            if ($row['CONSTRAINT_TYPE'] !== 'R') {
                /**
                 * This condition is not checked in SQL WHERE because of an Oracle Bug:
                 *
                 * {@see https://github.com/yiisoft/yii2/pull/8844}
                 */
                continue;
            }

            $name = $row['CONSTRAINT_NAME'];

            if (!isset($constraints[$name])) {
                $constraints[$name] = [
                    'tableName' => $row['TABLE_REF'],
                    'columns' => [],
                ];
            }

            $constraints[$name]['columns'][$row['COLUMN_NAME']] = $row['COLUMN_REF'];
        }

        foreach ($constraints as $constraint) {
            $name = current(array_keys($constraint));
            $table->foreignKey(array_merge([$constraint['tableName']], $constraint['columns']));
        }
    }

    /**
     * Returns all unique indexes for the given table.
     *
     * Each array element is of the following structure:.
     *
     * ```php
     * [
     *     'IndexName1' => ['col1' [, ...]],
     *     'IndexName2' => ['col2' [, ...]],
     * ]
     * ```
     *
     * @param TableSchema $table the table metadata.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array all unique indexes for the given table.
     */
    public function findUniqueIndexes(TableSchema $table): array
    {
        $query = <<<'SQL'
SELECT
    DIC.INDEX_NAME,
    DIC.COLUMN_NAME
FROM ALL_INDEXES DI
    INNER JOIN ALL_IND_COLUMNS DIC ON DI.TABLE_NAME = DIC.TABLE_NAME AND DI.INDEX_NAME = DIC.INDEX_NAME
WHERE
    DI.UNIQUENESS = 'UNIQUE'
    AND DIC.TABLE_OWNER = :schemaName
    AND DIC.TABLE_NAME = :tableName
ORDER BY DIC.TABLE_NAME, DIC.INDEX_NAME, DIC.COLUMN_POSITION
SQL;
        $result = [];

        $command = $this->db->createCommand($query, [
            ':tableName' => $table->getName(),
            ':schemaName' => $table->getschemaName(),
        ]);

        foreach ($command->queryAll() as $row) {
            $result[$row['INDEX_NAME']][] = $row['COLUMN_NAME'];
        }

        return $result;
    }

    /**
     * Extracts the data types for the given column.
     *
     * @param ColumnSchema $column
     * @param string $dbType DB type.
     * @param string|null $precision total number of digits.
     * @param string|null $scale number of digits on the right of the decimal separator.
     * @param string $length length for character types.
     */
    protected function extractColumnType(
        ColumnSchema $column,
        string $dbType,
        ?string $precision,
        ?string $scale,
        string $length
    ): void {
        $column->dbType($dbType);

        if (str_contains($dbType, 'FLOAT') || str_contains($dbType, 'DOUBLE')) {
            $column->type('double');
        } elseif (str_contains($dbType, 'NUMBER')) {
            if ($scale === null || $scale > 0) {
                $column->type('decimal');
            } else {
                $column->type('integer');
            }
        } elseif (str_contains($dbType, 'INTEGER')) {
            $column->type('integer');
        } elseif (str_contains($dbType, 'BLOB')) {
            $column->type('binary');
        } elseif (str_contains($dbType, 'CLOB')) {
            $column->type('text');
        } elseif (str_contains($dbType, 'TIMESTAMP')) {
            $column->type('timestamp');
        } else {
            $column->type('string');
        }
    }

    /**
     * Extracts size, precision and scale information from column's DB type.
     *
     * @param ColumnSchema $column
     * @param string $dbType the column's DB type.
     * @param string|null $precision total number of digits.
     * @param string|null $scale number of digits on the right of the decimal separator.
     * @param string $length length for character types.
     */
    protected function extractColumnSize(
        ColumnSchema $column,
        string $dbType,
        ?string $precision,
        ?string $scale,
        string $length
    ): void {
        $column->size(trim($length) === '' ? null : (int) $length);
        $column->precision(trim((string) $precision) === '' ? null : (int) $precision);
        $column->scale($scale === '' || $scale === null ? null : (int) $scale);
    }

    /**
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException|Throwable
     */
    public function insert($table, $columns): bool|array
    {
        $params = [];
        $returnParams = [];
        $sql = $this->db->getQueryBuilder()->insert($table, $columns, $params);
        $tableSchema = $this->getTableSchema($table);
        $returnColumns = $tableSchema->getPrimaryKey();

        if (!empty($returnColumns)) {
            $columnSchemas = $tableSchema->getColumns();

            $returning = [];
            foreach ($returnColumns as $name) {
                $phName = QueryBuilder::PARAM_PREFIX . (count($params) + count($returnParams));

                $returnParams[$phName] = [
                    'column' => $name,
                    'value' => '',
                ];

                if (!isset($columnSchemas[$name]) || $columnSchemas[$name]->getPhpType() !== 'integer') {
                    $returnParams[$phName]['dataType'] = PDO::PARAM_STR;
                } else {
                    $returnParams[$phName]['dataType'] = PDO::PARAM_INT;
                }

                $returnParams[$phName]['size'] = $columnSchemas[$name]->getSize() ?? -1;

                $returning[] = $this->db->getQuoter()->quoteColumnName($name);
            }

            $sql .= ' RETURNING ' . implode(', ', $returning) . ' INTO ' . implode(', ', array_keys($returnParams));
        }

        $command = $this->db->createCommand($sql, $params);

        $command->prepare(false);

        foreach ($returnParams as $name => &$value) {
            $command->getPdoStatement()->bindParam($name, $value['value'], $value['dataType'], $value['size']);
        }

        if (!$command->execute()) {
            return false;
        }

        $result = [];
        foreach ($returnParams as $value) {
            $result[$value['column']] = $value['value'];
        }

        return $result;
    }

    /**
     * Loads multiple types of constraints and returns the specified ones.
     *
     * @param string $tableName table name.
     * @param string $returnType return type:
     * - primaryKey
     * - foreignKeys
     * - uniques
     * - checks
     *
     * @throws Exception|InvalidConfigException|NotSupportedException|Throwable
     *
     * @return mixed constraints.
     */
    private function loadTableConstraints(string $tableName, string $returnType): mixed
    {
        $sql = <<<SQL
        SELECT
            "uc"."CONSTRAINT_NAME" AS "name",
            "uccol"."COLUMN_NAME" AS "column_name",
            "uc"."CONSTRAINT_TYPE" AS "type",
            "fuc"."OWNER" AS "foreign_table_schema",
            "fuc"."TABLE_NAME" AS "foreign_table_name",
            "fuccol"."COLUMN_NAME" AS "foreign_column_name",
            "uc"."DELETE_RULE" AS "on_delete",
            "uc"."SEARCH_CONDITION" AS "check_expr"
        FROM "USER_CONSTRAINTS" "uc"
        INNER JOIN "USER_CONS_COLUMNS" "uccol"
        ON "uccol"."OWNER" = "uc"."OWNER" AND "uccol"."CONSTRAINT_NAME" = "uc"."CONSTRAINT_NAME"
        LEFT JOIN "USER_CONSTRAINTS" "fuc"
        ON "fuc"."OWNER" = "uc"."R_OWNER" AND "fuc"."CONSTRAINT_NAME" = "uc"."R_CONSTRAINT_NAME"
        LEFT JOIN "USER_CONS_COLUMNS" "fuccol"
        ON "fuccol"."OWNER" = "fuc"."OWNER" AND "fuccol"."CONSTRAINT_NAME" = "fuc"."CONSTRAINT_NAME" AND "fuccol"."POSITION" = "uccol"."POSITION"
        WHERE "uc"."OWNER" = :schemaName AND "uc"."TABLE_NAME" = :tableName
        ORDER BY "uccol"."POSITION" ASC
        SQL;

        $resolvedName = $this->resolveTableName($tableName);

        $constraints = $this->db->createCommand($sql, [
            ':schemaName' => $resolvedName->getSchemaName(),
            ':tableName' => $resolvedName->getName(),
        ])->queryAll();

        $constraints = $this->normalizePdoRowKeyCase($constraints, true);

        $constraints = ArrayHelper::index($constraints, null, ['type', 'name']);

        $result = [
            'primaryKey' => null,
            'foreignKeys' => [],
            'uniques' => [],
            'checks' => [],
        ];

        /**
         * @var string $type
         * @var array $names
         */
        foreach ($constraints as $type => $names) {
            /**
             * @psalm-var object|string|null $name
             * @psalm-var ConstraintArray $constraint
             */
            foreach ($names as $name => $constraint) {
                switch ($type) {
                    case 'P':
                        $result['primaryKey'] = (new Constraint())
                            ->name($name)
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'));
                        break;
                    case 'R':
                        $result['foreignKeys'][] = (new ForeignKeyConstraint())
                            ->name($name)
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->foreignSchemaName($constraint[0]['foreign_table_schema'])
                            ->foreignTableName($constraint[0]['foreign_table_name'])
                            ->foreignColumnNames(ArrayHelper::getColumn($constraint, 'foreign_column_name'))
                            ->onDelete($constraint[0]['on_delete'])
                            ->onUpdate(null);
                        break;
                    case 'U':
                        $result['uniques'][] = (new Constraint())
                            ->name($name)
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'));
                        break;
                    case 'C':
                        $result['checks'][] = (new CheckConstraint())
                            ->name($name)
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->expression($constraint[0]['check_expr']);
                        break;
                }
            }
        }

        foreach ($result as $type => $data) {
            $this->setTableMetadata($tableName, $type, $data);
        }

        return $result[$returnType];
    }

    /**
     * Creates a column schema for the database.
     *
     * This method may be overridden by child classes to create a DBMS-specific column schema.
     *
     * @return ColumnSchema column schema instance.
     */
    protected function createColumnSchema(): ColumnSchema
    {
        return new ColumnSchema();
    }

    public function rollBackSavepoint(string $name): void
    {
        $this->db->createCommand("ROLLBACK TO SAVEPOINT $name")->execute();
    }

    public function setTransactionIsolationLevel(string $level): void
    {
        $this->db->createCommand("SET TRANSACTION ISOLATION LEVEL $level")->execute();
    }

    /**
     * Returns the actual name of a given table name.
     *
     * This method will strip off curly brackets from the given table name and replace the percentage character '%' with
     * {@see ConnectionInterface::tablePrefix}.
     *
     * @param string $name the table name to be converted.
     *
     * @return string the real name of the given table name.
     */
    public function getRawTableName(string $name): string
    {
        if (str_contains($name, '{{')) {
            $name = preg_replace('/{{(.*?)}}/', '\1', $name);

            return str_replace('%', $this->db->getTablePrefix(), $name);
        }

        return $name;
    }

    /**
     * Returns the cache key for the specified table name.
     *
     * @param string $name the table name.
     *
     * @return array the cache key.
     */
    protected function getCacheKey(string $name): array
    {
        return [
            __CLASS__,
            $this->db->getDriver()->getDsn(),
            $this->db->getDriver()->getUsername(),
            $this->getRawTableName($name),
        ];
    }

    /**
     * Returns the cache tag name.
     *
     * This allows {@see refresh()} to invalidate all cached table schemas.
     *
     * @return string the cache tag name.
     */
    protected function getCacheTag(): string
    {
        return md5(serialize([
            __CLASS__,
            $this->db->getDriver()->getDsn(),
            $this->db->getDriver()->getUsername(),
        ]));
    }

    /**
     * Changes row's array key case to lower if PDO's one is set to uppercase.
     *
     * @param array $row row's array or an array of row's arrays.
     * @param bool $multiple whether multiple rows or a single row passed.
     *
     * @throws Exception
     *
     * @return array normalized row or rows.
     */
    protected function normalizePdoRowKeyCase(array $row, bool $multiple): array
    {
        if ($this->db->getSlavePdo()->getAttribute(PDO::ATTR_CASE) !== PDO::CASE_UPPER) {
            return $row;
        }

        if ($multiple) {
            return array_map(static function (array $row) {
                return array_change_key_case($row, CASE_LOWER);
            }, $row);
        }

        return array_change_key_case($row, CASE_LOWER);
    }

    /**
     * @return bool whether this DBMS supports [savepoint](http://en.wikipedia.org/wiki/Savepoint).
     */
    public function supportsSavepoint(): bool
    {
        return $this->db->isSavepointEnabled();
    }

    /**
     * Creates a new savepoint.
     *
     * @param string $name the savepoint name
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function createSavepoint(string $name): void
    {
        $this->db->createCommand("SAVEPOINT $name")->execute();
    }
}
