<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Throwable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Schema as AbstractSchema;
use Yiisoft\Db\Schema\TableSchemaInterface;

use function array_merge;
use function is_array;
use function md5;
use function serialize;
use function str_contains;
use function stripos;
use function strlen;
use function substr;
use function trim;

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
final class Schema extends AbstractSchema
{
    public function __construct(protected ConnectionInterface $db, SchemaCache $schemaCache, string $defaultSchema)
    {
        $this->defaultSchema = $defaultSchema;
        parent::__construct($db, $schemaCache);
    }

    protected function resolveTableName(string $name): TableSchemaInterface
    {
        $resolvedName = new TableSchema();

        $parts = array_reverse(
            $this->db->getQuoter()->getTableNameParts($name)
        );

        $resolvedName->name($parts[0] ?? '');
        $resolvedName->schemaName($parts[1] ?? $this->defaultSchema);

        $resolvedName->fullName(
            $resolvedName->getSchemaName() !== $this->defaultSchema ?
            implode('.', array_reverse($parts)) : $resolvedName->getName()
        );

        return $resolvedName;
    }

    /**
     * @link https://docs.oracle.com/cd/B28359_01/server.111/b28337/tdpsg_user_accounts.htm
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

    protected function findTableComment(TableSchemaInterface $tableSchema): void
    {
        $sql = <<<SQL
        SELECT "COMMENTS"
        FROM ALL_TAB_COMMENTS
        WHERE
              "OWNER" = :schemaName AND
              "TABLE_NAME" = :tableName
        SQL;

        $comment = $this->db->createCommand($sql, [
            ':schemaName' => $tableSchema->getSchemaName(),
            ':tableName' => $tableSchema->getName(),
        ])->queryScalar();

        $tableSchema->comment(is_string($comment) ? $comment : null);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
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

        /** @psalm-var string[][] $rows */
        foreach ($rows as $row) {
            /** @psalm-var string[] $row */
            $row = $this->normalizeRowKeyCase($row, false);
            $names[] = $row['table_name'];
        }

        return $names;
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function loadTableSchema(string $name): TableSchemaInterface|null
    {
        $table = $this->resolveTableName($name);
        $this->findTableComment($table);

        if ($this->findColumns($table)) {
            $this->findConstraints($table);
            return $table;
        }

        return null;
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    protected function loadTablePrimaryKey(string $tableName): Constraint|null
    {
        /** @var mixed */
        $tablePrimaryKey = $this->loadTableConstraints($tableName, self::PRIMARY_KEY);
        return $tablePrimaryKey instanceof Constraint ? $tablePrimaryKey : null;
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    protected function loadTableForeignKeys(string $tableName): array
    {
        /** @var mixed */
        $tableForeingKeys = $this->loadTableConstraints($tableName, self::FOREIGN_KEYS);
        return is_array($tableForeingKeys) ? $tableForeingKeys : [];
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
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

        /** @psalm-var array[] $indexes */
        $indexes = $this->normalizeRowKeyCase($indexes, true);
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
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    protected function loadTableUniques(string $tableName): array
    {
        /** @var mixed */
        $tableUniques = $this->loadTableConstraints($tableName, self::UNIQUES);
        return is_array($tableUniques) ? $tableUniques : [];
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    protected function loadTableChecks(string $tableName): array
    {
        /** @var mixed */
        $tableCheck = $this->loadTableConstraints($tableName, self::CHECKS);
        return is_array($tableCheck) ? $tableCheck : [];
    }

    /**
     * @throws NotSupportedException if this method is called.
     */
    protected function loadTableDefaultValues(string $tableName): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by Oracle.');
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
     *
     * @psalm-param string[]|int|string|null $length
     */
    public function createColumnSchemaBuilder(string $type, array|int|string $length = null): ColumnSchemaBuilder
    {
        return new ColumnSchemaBuilder($type, $length);
    }

    /**
     * Collects the table column metadata.
     *
     * @param TableSchemaInterface $table the table schema.
     *
     * @throws Exception
     * @throws Throwable
     *
     * @return bool whether the table exists.
     */
    protected function findColumns(TableSchemaInterface $table): bool
    {
        $sql = <<<SQL
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

        $columns = $this->db->createCommand($sql, [
            ':tableName' => $table->getName(),
            ':schemaName' => $table->getSchemaName(),
        ])->queryAll();

        if ($columns === []) {
            return false;
        }

        /** @psalm-var string[][] $columns */
        foreach ($columns as $column) {
            $column = $this->normalizeRowKeyCase($column, false);

            $c = $this->createColumn($column);

            $table->columns($c->getName(), $c);
        }

        return true;
    }

    /**
     * Sequence name of table.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return bool|float|int|string|null whether the sequence exists.
     *
     * @internal TableSchemaInterface `$table->getName()` the table schema.
     */
    protected function getTableSequenceName(string $tableName): bool|float|int|string|null
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
     * Creates ColumnSchema instance.
     */
    protected function createColumn(array|string $column): ColumnSchema
    {
        $c = $this->createColumnSchema();

        /**
         * @psalm-var array{
         *   column_name: string,
         *   data_type: string,
         *   data_precision: string,
         *   data_scale: string,
         *   data_length: string,
         *   nullable: string,
         *   data_default: string|null,
         *   column_comment: string|null
         * } $column
         */
        $c->name($column['column_name']);
        $c->allowNull($column['nullable'] === 'Y');
        $c->comment($column['column_comment'] ?? '');
        $c->primaryKey(false);

        $this->extractColumnType(
            $c,
            $column['data_type'],
            $column['data_precision'],
            $column['data_scale'],
            $column['data_length']
        );

        $this->extractColumnSize(
            $c,
            $column['data_type'],
            $column['data_precision'],
            $column['data_scale'],
            $column['data_length']
        );

        $c->phpType($this->getColumnPhpType($c));

        if (!$c->isPrimaryKey()) {
            if ($column['data_default'] !== null && stripos($column['data_default'], 'timestamp') !== false) {
                $c->defaultValue(null);
            } else {
                $defaultValue = $column['data_default'];

                if ($c->getType() === 'timestamp' && $defaultValue === 'CURRENT_TIMESTAMP') {
                    $c->defaultValue(new Expression('CURRENT_TIMESTAMP'));
                } else {
                    if ($defaultValue !== null) {
                        if (
                            ($len = strlen($defaultValue)) > 2 &&
                            $defaultValue[0] === "'" &&
                            $defaultValue[$len - 1] === "'"
                        ) {
                            $defaultValue = substr((string) $column['data_default'], 1, -1);
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
     * Finds constraints and fills them into TableSchemaInterface object passed.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @psalm-suppress PossiblyNullArrayOffset
     */
    protected function findConstraints(TableSchemaInterface $table): void
    {
        $sql = <<<SQL
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

        /**
         * @psalm-var array{
         *   array{
         *     constraint_name: string,
         *     constraint_type: string,
         *     column_name: string,
         *     position: string|null,
         *     r_constraint_name: string|null,
         *     table_ref: string|null,
         *     column_ref: string|null,
         *     table_name: string
         *   }
         * } $rows
         */
        $rows = $this->db->createCommand(
            $sql,
            [':tableName' => $table->getName(), ':schemaName' => $table->getSchemaName()]
        )->queryAll();

        $constraints = [];

        foreach ($rows as $row) {
            /** @psalm-var string[] $row */
            $row = $this->normalizeRowKeyCase($row, false);

            if ($row['constraint_type'] === 'P') {
                $table->getColumns()[$row['column_name']]->primaryKey(true);
                $table->primaryKey($row['column_name']);

                if (empty($table->getSequenceName())) {
                    $table->sequenceName((string) $this->getTableSequenceName($table->getName()));
                }
            }

            if ($row['constraint_type'] !== 'R') {
                /**
                 * This condition is not checked in SQL WHERE because of an Oracle Bug:
                 *
                 * {@see https://github.com/yiisoft/yii2/pull/8844}
                 */
                continue;
            }

            $name = $row['constraint_name'];

            if (!isset($constraints[$name])) {
                $constraints[$name] = [
                    'tableName' => $row['table_ref'],
                    'columns' => [],
                ];
            }

            $constraints[$name]['columns'][$row['column_name']] = $row['column_ref'];
        }

        foreach ($constraints as $index => $constraint) {
            $table->foreignKey($index, array_merge([$constraint['tableName']], $constraint['columns']));
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
     * @param TableSchemaInterface $table the table metadata.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array all unique indexes for the given table.
     */
    public function findUniqueIndexes(TableSchemaInterface $table): array
    {
        $query = <<<SQL
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

        $rows = $this->db->createCommand(
            $query,
            [':tableName' => $table->getName(), ':schemaName' => $table->getschemaName()]
        )->queryAll();

        /** @psalm-var array<array{INDEX_NAME: string, COLUMN_NAME: string}> $rows */
        foreach ($rows as $row) {
            $result[$row['INDEX_NAME']][] = $row['COLUMN_NAME'];
        }

        return $result;
    }

    /**
     * Extracts the data types for the given column.
     *
     * @param string $dbType DB type.
     * @param string|null $precision total number of digits.
     * @param string|null $scale number of digits on the right of the decimal separator.
     * @param string $length length for character types.
     */
    protected function extractColumnType(
        ColumnSchema $column,
        string $dbType,
        string|null $precision,
        string|null $scale,
        string $length
    ): void {
        $column->dbType($dbType);

        if (str_contains($dbType, 'FLOAT') || str_contains($dbType, 'DOUBLE')) {
            $column->type(self::TYPE_DOUBLE);
        } elseif (str_contains($dbType, 'NUMBER')) {
            if ($scale === null || $scale > 0) {
                $column->type(self::TYPE_DECIMAL);
            } else {
                $column->type(self::TYPE_INTEGER);
            }
        } elseif (str_contains($dbType, 'BLOB')) {
            $column->type(self::TYPE_BINARY);
        } elseif (str_contains($dbType, 'CLOB')) {
            $column->type(self::TYPE_TEXT);
        } elseif (str_contains($dbType, 'TIMESTAMP')) {
            $column->type(self::TYPE_TIMESTAMP);
        } else {
            $column->type(self::TYPE_STRING);
        }
    }

    /**
     * Extracts size, precision and scale information from column's DB type.
     *
     * @param string $dbType the column's DB type.
     * @param string|null $precision total number of digits.
     * @param string|null $scale number of digits on the right of the decimal separator.
     * @param string $length length for character types.
     */
    protected function extractColumnSize(
        ColumnSchema $column,
        string $dbType,
        string|null $precision,
        string|null $scale,
        string $length
    ): void {
        $column->size(trim($length) === '' ? null : (int) $length);
        $column->precision(trim((string) $precision) === '' ? null : (int) $precision);
        $column->scale($scale === '' || $scale === null ? null : (int) $scale);
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
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
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

        /** @var Constraint[] $constraints */
        $constraints = $this->normalizeRowKeyCase($constraints, true);
        $constraints = ArrayHelper::index($constraints, null, ['type', 'name']);

        $result = [
            self::PRIMARY_KEY => null,
            self::FOREIGN_KEYS => [],
            self::UNIQUES => [],
            self::CHECKS => [],
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
                        $result[self::PRIMARY_KEY] = (new Constraint())
                            ->name($name)
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'));
                        break;
                    case 'R':
                        $result[self::FOREIGN_KEYS][] = (new ForeignKeyConstraint())
                            ->name($name)
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->foreignSchemaName($constraint[0]['foreign_table_schema'])
                            ->foreignTableName($constraint[0]['foreign_table_name'])
                            ->foreignColumnNames(ArrayHelper::getColumn($constraint, 'foreign_column_name'))
                            ->onDelete($constraint[0]['on_delete'])
                            ->onUpdate(null);
                        break;
                    case 'U':
                        $result[self::UNIQUES][] = (new Constraint())
                            ->name($name)
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'));
                        break;
                    case 'C':
                        $result[self::CHECKS][] = (new CheckConstraint())
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

    protected function findViewNames(string $schema = ''): array
    {
        /** @psalm-var string[][] $views */
        $views = $this->db->createCommand(
            <<<SQL
            select view_name from user_views
            SQL,
        )->queryAll();

        foreach ($views as $key => $view) {
            $views[$key] = $view['VIEW_NAME'];
        }

        return $views;
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
        return array_merge([self::class], $this->db->getCacheKey(), [$this->getRawTableName($name)]);
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
        return md5(serialize(array_merge([self::class], $this->db->getCacheKey())));
    }
}
