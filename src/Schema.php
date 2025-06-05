<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Driver\Pdo\AbstractPdoSchema;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Helper\DbArrayHelper;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;

use function array_change_key_case;
use function array_column;
use function array_map;
use function array_reverse;
use function implode;
use function in_array;
use function preg_replace;
use function strtolower;

/**
 * Implements the Oracle Server specific schema, supporting Oracle Server 11C and above.
 *
 * @psalm-type ColumnArray = array{
 *   column_name: string,
 *   data_type: string,
 *   data_scale: string|null,
 *   identity_column: string,
 *   size: string|null,
 *   nullable: string,
 *   data_default: string|null,
 *   constraint_type: string|null,
 *   check: string|null,
 *   column_comment: string|null,
 *   schema: string,
 *   table: string
 * }
 *
 * @psalm-type ConstraintArray = array<
 *   array-key,
 *   array {
 *     name: string,
 *     column_name: string,
 *     type: string,
 *     foreign_table_schema: string,
 *     foreign_table_name: string,
 *     foreign_column_name: string,
 *     on_delete: ReferentialAction::*,
 *     check_expr: string
 *   }
 * >
 */
final class Schema extends AbstractPdoSchema
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

        /** @var string[] */
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
            SELECT OBJECT_NAME
            FROM ALL_OBJECTS
            WHERE OBJECT_TYPE IN ('TABLE', 'VIEW', 'MATERIALIZED VIEW') AND OWNER = :schema
            ORDER BY OBJECT_NAME
            SQL;
            $command = $this->db->createCommand($sql, [':schema' => $schema]);
        }

        /** @var string[] */
        return $command->queryColumn();
    }

    /**
     * @param array{
     *     "oci:decl_type": int|string,
     *     native_type: string,
     *     pdo_type: int,
     *     scale: int,
     *     table?: string,
     *     flags: string[],
     *     name: string,
     *     len: int,
     *     precision: int,
     * } $metadata
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    protected function loadResultColumn(array $metadata): ColumnInterface|null
    {
        if (empty($metadata['oci:decl_type'])) {
            return null;
        }

        $dbType = match ($metadata['oci:decl_type']) {
            119 => 'json',
            'TIMESTAMP WITH TIMEZONE' => 'timestamp with time zone',
            'TIMESTAMP WITH LOCAL TIMEZONE' => 'timestamp with local time zone',
            default => strtolower((string) $metadata['oci:decl_type']),
        };

        $columnInfo = ['fromResult' => true];

        if (!empty($metadata['table'])) {
            $columnInfo['table'] = $metadata['table'];
            $columnInfo['name'] = $metadata['name'];
        } elseif (!empty($metadata['name'])) {
            $columnInfo['name'] = $metadata['name'];
        }

        if ($metadata['pdo_type'] === 3) {
            $columnInfo['type'] = ColumnType::BINARY;
        }

        if (!empty($metadata['precision'])) {
            $columnInfo['size'] = $metadata['precision'];
        }

        /** @psalm-suppress PossiblyUndefinedArrayOffset, InvalidArrayOffset */
        match ($dbType) {
            'timestamp',
            'timestamp with time zone',
            'timestamp with local time zone' => $columnInfo['size'] = $metadata['scale'],
            'interval day to second',
            'interval year to month' =>
                [$columnInfo['size'], $columnInfo['scale']] = [$metadata['scale'], $metadata['precision']],
            'number' => $metadata['scale'] !== -127 ? $columnInfo['scale'] = $metadata['scale'] : null,
            'float' => null,
            default => $columnInfo['size'] = $metadata['len'],
        };

        if ($dbType === 'timestamp with local time zone') {
            $columnInfo['dbTimezone'] = $this->db->getServerInfo()->getTimezone();
        }

        $columnInfo['notNull'] = in_array('not_null', $metadata['flags'], true);

        /** @psalm-suppress MixedArgumentTypeCoercion */
        return $this->db->getColumnFactory()->fromDbType($dbType, $columnInfo);
    }

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

    protected function loadTablePrimaryKey(string $tableName): IndexConstraint|null
    {
        /** @var IndexConstraint|null */
        return $this->loadTableConstraints($tableName, self::PRIMARY_KEY);
    }

    protected function loadTableForeignKeys(string $tableName): array
    {
        /** @var ForeignKeyConstraint[] */
        return $this->loadTableConstraints($tableName, self::FOREIGN_KEYS);
    }

    protected function loadTableIndexes(string $tableName): array
    {
        $sql = <<<SQL
        SELECT "ui"."INDEX_NAME" AS "name", "uicol"."COLUMN_NAME" AS "column_name",
        CASE "ui"."UNIQUENESS" WHEN 'UNIQUE' THEN 1 ELSE 0 END AS "is_unique",
        CASE WHEN "uc"."CONSTRAINT_NAME" IS NOT NULL THEN 1 ELSE 0 END AS "is_primary_key"
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

        $indexes = array_map(array_change_key_case(...), $indexes);
        $indexes = DbArrayHelper::arrange($indexes, ['name']);

        $result = [];

        /**
         * @var string $name
         * @var array[] $index
         */
        foreach ($indexes as $name => $index) {
            $columnNames = array_column($index, 'column_name');

            if ($columnNames === [null]) {
                $columnNames = [];
            }

            /** @var string[] $columnNames */
            $result[] = new IndexConstraint(
                $name,
                $columnNames,
                (bool) $index[0]['is_unique'],
                (bool) $index[0]['is_primary_key'],
            );
        }

        return $result;
    }

    protected function loadTableUniques(string $tableName): array
    {
        /** @var IndexConstraint[] */
        return $this->loadTableConstraints($tableName, self::UNIQUES);
    }

    protected function loadTableChecks(string $tableName): array
    {
        /** @var CheckConstraint[] */
        return $this->loadTableConstraints($tableName, self::CHECKS);
    }

    /**
     * @throws NotSupportedException If this method is called.
     */
    protected function loadTableDefaultValues(string $tableName): array
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by Oracle.');
    }

    /**
     * Collects the table column metadata.
     *
     * @param TableSchemaInterface $table The table schema.
     *
     * @return bool Whether the table exists.
     */
    protected function findColumns(TableSchemaInterface $table): bool
    {
        $schemaName = $table->getSchemaName();
        $tableName = $table->getName();

        $sql = <<<SQL
        WITH C AS (
            SELECT AC.CONSTRAINT_TYPE, AC.SEARCH_CONDITION, ACC.COLUMN_NAME
            FROM ALL_CONSTRAINTS AC
            INNER JOIN ALL_CONS_COLUMNS ACC
                ON ACC.OWNER = AC.OWNER
                AND ACC.TABLE_NAME = AC.TABLE_NAME
                AND ACC.CONSTRAINT_NAME = AC.CONSTRAINT_NAME
            LEFT JOIN ALL_CONS_COLUMNS ACC2
                ON ACC2.OWNER = AC.OWNER
                AND ACC2.TABLE_NAME = AC.TABLE_NAME
                AND ACC2.CONSTRAINT_NAME = AC.CONSTRAINT_NAME
                AND ACC2.COLUMN_NAME != ACC.COLUMN_NAME
            WHERE AC.OWNER = :schemaName2
                AND AC.TABLE_NAME = :tableName2
                AND (AC.CONSTRAINT_TYPE = 'P'
                    OR AC.CONSTRAINT_TYPE = 'U' AND ACC2.COLUMN_NAME IS NULL
                    OR AC.CONSTRAINT_TYPE = 'C' AND ACC2.COLUMN_NAME IS NULL AND AC.SEARCH_CONDITION_VC != '"' || ACC.COLUMN_NAME || '" IS NOT NULL')
        )
        SELECT
            A.COLUMN_NAME,
            A.DATA_TYPE,
            A.DATA_SCALE,
            A.IDENTITY_COLUMN,
            (CASE WHEN A.CHAR_LENGTH > 0 THEN A.CHAR_LENGTH ELSE A.DATA_PRECISION END) AS "size",
            A.NULLABLE,
            A.DATA_DEFAULT,
            C.CONSTRAINT_TYPE,
            C2.SEARCH_CONDITION AS "check",
            COM.COMMENTS AS COLUMN_COMMENT
        FROM ALL_TAB_COLUMNS A
        INNER JOIN ALL_OBJECTS B
            ON B.OWNER = A.OWNER
            AND B.OBJECT_NAME = A.TABLE_NAME
        LEFT JOIN ALL_COL_COMMENTS COM
            ON COM.OWNER = A.OWNER
            AND COM.TABLE_NAME = A.TABLE_NAME
            AND COM.COLUMN_NAME = A.COLUMN_NAME
        LEFT JOIN C
            ON C.COLUMN_NAME = A.COLUMN_NAME
            AND C.CONSTRAINT_TYPE IN ('P', 'U')
        LEFT JOIN C C2
            ON C2.COLUMN_NAME = A.COLUMN_NAME
            AND C2.CONSTRAINT_TYPE = 'C'
        WHERE A.OWNER = :schemaName
            AND A.TABLE_NAME = :tableName
            AND B.OBJECT_TYPE IN ('TABLE', 'VIEW', 'MATERIALIZED VIEW')
        ORDER BY A.COLUMN_ID
        SQL;

        $columns = $this->db->createCommand($sql, [
            ':schemaName' => $schemaName,
            ':schemaName2' => $schemaName,
            ':tableName' => $tableName,
            ':tableName2' => $tableName,
        ])->queryAll();

        if ($columns === []) {
            return false;
        }

        /** @psalm-var string[][] $info */
        foreach ($columns as $info) {
            $info = array_change_key_case($info);

            $info['schema'] = $schemaName;
            $info['table'] = $tableName;

            /** @psalm-var ColumnArray $info */
            $column = $this->loadColumn($info);

            $table->column($info['column_name'], $column);
        }

        return true;
    }

    /**
     * Sequence name of table.
     *
     * @return string|null Whether the sequence exists.
     *
     * @internal TableSchemaInterface `$table->getName()` The table schema.
     */
    protected function getTableSequenceName(string $tableName): string|null
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

        /** @var string|null */
        return $sequenceName === false ? null : $sequenceName;
    }

    /**
     * Loads the column information into a {@see ColumnInterface} object.
     *
     * @param array $info The column information.
     *
     * @return ColumnInterface The column object.
     *
     * @psalm-param ColumnArray $info The column information.
     */
    private function loadColumn(array $info): ColumnInterface
    {
        /** @var string $dbType */
        $dbType = preg_replace('/\([^)]+\)/', '', $info['data_type']);
        $dbType = strtolower($dbType);

        match ($dbType) {
            'timestamp',
            'timestamp with time zone',
            'timestamp with local time zone',
            'interval day to second',
            'interval year to month' => [$info['size'], $info['data_scale']] = [$info['data_scale'], $info['size']],
            default => null,
        };

        $columnInfo = [
            'autoIncrement' => $info['identity_column'] === 'YES',
            'check' => $info['check'],
            'comment' => $info['column_comment'],
            'defaultValueRaw' => $info['data_default'],
            'name' => $info['column_name'],
            'notNull' => $info['nullable'] !== 'Y',
            'primaryKey' => $info['constraint_type'] === 'P',
            'scale' => $info['data_scale'] !== null ? (int) $info['data_scale'] : null,
            'schema' => $info['schema'],
            'size' => $info['size'] !== null ? (int) $info['size'] : null,
            'table' => $info['table'],
            'unique' => $info['constraint_type'] === 'U',
        ];

        if ($dbType === 'timestamp with local time zone') {
            $columnInfo['dbTimezone'] = $this->db->getServerInfo()->getTimezone();
        }

        return $this->db->getColumnFactory()->fromDbType($dbType, $columnInfo);
    }

    /**
     * Finds constraints and fills them into TableSchemaInterface object passed.
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
            $row = array_change_key_case($row);

            if ($row['constraint_type'] === 'P') {
                $table->getColumns()[$row['column_name']]->primaryKey(true);
                $table->primaryKey($row['column_name']);

                if (empty($table->getSequenceName())) {
                    $table->sequenceName($this->getTableSequenceName($table->getName()));
                }
            }

            if ($row['constraint_type'] !== 'R') {
                /**
                 * This condition isn't checked in `WHERE` because of an Oracle Bug:
                 *
                 * @link https://github.com/yiisoft/yii2/pull/8844
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
            $table->foreignKey($index, [$constraint['tableName'], ...$constraint['columns']]);
        }
    }

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
     * Loads multiple types of constraints and returns the specified ones.
     *
     * @param string $tableName The table name.
     * @param string $returnType The return type:
     * - primaryKey
     * - foreignKeys
     * - uniques
     * - checks
     *
     * @return CheckConstraint[]|ForeignKeyConstraint[]|IndexConstraint|IndexConstraint[]|null Constraints.
     */
    private function loadTableConstraints(string $tableName, string $returnType): array|IndexConstraint|null
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

        $constraints = array_map(array_change_key_case(...), $constraints);
        $constraints = DbArrayHelper::arrange($constraints, ['type', 'name']);

        $result = [
            self::PRIMARY_KEY => null,
            self::FOREIGN_KEYS => [],
            self::UNIQUES => [],
            self::CHECKS => [],
        ];

        foreach ($constraints as $type => $names) {
            /**
             * @var string $name
             * @psalm-var ConstraintArray $constraint
             */
            foreach ($names as $name => $constraint) {
                switch ($type) {
                    case 'P':
                        $result[self::PRIMARY_KEY] = new IndexConstraint(
                            $name,
                            array_column($constraint, 'column_name'),
                            true,
                            true,
                        );
                        break;
                    case 'R':
                        $result[self::FOREIGN_KEYS][] = new ForeignKeyConstraint(
                            $name,
                            array_column($constraint, 'column_name'),
                            $constraint[0]['foreign_table_schema'] . '.' . $constraint[0]['foreign_table_name'],
                            array_column($constraint, 'foreign_column_name'),
                            null,
                            $constraint[0]['on_delete'],
                        );
                        break;
                    case 'U':
                        $result[self::UNIQUES][] = new IndexConstraint(
                            $name,
                            array_column($constraint, 'column_name'),
                            true,
                        );
                        break;
                    case 'C':
                        $result[self::CHECKS][] = new CheckConstraint(
                            $name,
                            array_column($constraint, 'column_name'),
                            $constraint[0]['check_expr'],
                        );
                        break;
                }
            }
        }

        foreach ($result as $type => $data) {
            $this->setTableMetadata($tableName, $type, $data);
        }

        return $result[$returnType];
    }

    protected function findViewNames(string $schema = ''): array
    {
        $sql = match ($schema) {
            '' => 'SELECT VIEW_NAME FROM USER_VIEWS',
            default => "SELECT VIEW_NAME FROM ALL_VIEWS WHERE OWNER = '$schema'",
        };

        /** @var string[] */
        return $this->db->createCommand($sql)->queryColumn();
    }
}
