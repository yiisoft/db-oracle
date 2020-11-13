<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\ConnectionInterface;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Oracle\Conditions\InConditionBuilder;
use Yiisoft\Db\Oracle\Conditions\LikeConditionBuilder;
use Yiisoft\Db\Oracle\Schema;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryBuilder as AbstractQueryBuilder;
use Yiisoft\Db\Query\Conditions\InCondition;
use Yiisoft\Db\Query\Conditions\LikeCondition;
use Yiisoft\Strings\NumericHelper;

/**
 * QueryBuilder is the query builder for Oracle databases.
 */
final class QueryBuilder extends AbstractQueryBuilder
{
    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     */
    protected array $typeMap = [
        Schema::TYPE_PK => 'NUMBER(10) NOT NULL PRIMARY KEY',
        Schema::TYPE_UPK => 'NUMBER(10) UNSIGNED NOT NULL PRIMARY KEY',
        Schema::TYPE_BIGPK => 'NUMBER(20) NOT NULL PRIMARY KEY',
        Schema::TYPE_UBIGPK => 'NUMBER(20) UNSIGNED NOT NULL PRIMARY KEY',
        Schema::TYPE_CHAR => 'CHAR(1)',
        Schema::TYPE_STRING => 'VARCHAR2(255)',
        Schema::TYPE_TEXT => 'CLOB',
        Schema::TYPE_TINYINT => 'NUMBER(3)',
        Schema::TYPE_SMALLINT => 'NUMBER(5)',
        Schema::TYPE_INTEGER => 'NUMBER(10)',
        Schema::TYPE_BIGINT => 'NUMBER(20)',
        Schema::TYPE_FLOAT => 'NUMBER',
        Schema::TYPE_DOUBLE => 'NUMBER',
        Schema::TYPE_DECIMAL => 'NUMBER',
        Schema::TYPE_DATETIME => 'TIMESTAMP',
        Schema::TYPE_TIMESTAMP => 'TIMESTAMP',
        Schema::TYPE_TIME => 'TIMESTAMP',
        Schema::TYPE_DATE => 'DATE',
        Schema::TYPE_BINARY => 'BLOB',
        Schema::TYPE_BOOLEAN => 'NUMBER(1)',
        Schema::TYPE_MONEY => 'NUMBER(19,4)',
    ];


    protected function defaultExpressionBuilders(): array
    {
        return array_merge(parent::defaultExpressionBuilders(), [
            InCondition::class => InConditionBuilder::class,
            LikeCondition::class => LikeConditionBuilder::class,
        ]);
    }

    public function buildOrderByAndLimit(string $sql, array $orderBy, $limit, $offset, array &$params = []): string
    {
        $orderBy = $this->buildOrderBy($orderBy, $params);

        if ($orderBy !== '') {
            $sql .= $this->separator . $orderBy;
        }

        $filters = [];
        if ($this->hasOffset($offset)) {
            $filters[] = 'rowNumId > ' . $offset;
        }

        if ($this->hasLimit($limit)) {
            $filters[] = 'rownum <= ' . $limit;
        }

        if (empty($filters)) {
            return $sql;
        }

        $filter = implode(' AND ', $filters);
        return <<<EOD
WITH USER_SQL AS ($sql),
    PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
SELECT *
FROM PAGINATION
WHERE $filter
EOD;
    }

    /**
     * Builds a SQL statement for renaming a DB table.
     *
     * @param string $table the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for renaming a DB table.
     */
    public function renameTable(string $table, string $newName): string
    {
        return 'ALTER TABLE ' . $this->getDb()->quoteTableName($table) . ' RENAME TO ' . $this->getDb()->quoteTableName($newName);
    }

    /**
     * Builds a SQL statement for changing the definition of a column.
     *
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the
     * method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type the new column type. The [[getColumnType]] method will be invoked to convert abstract column
     * type (if any) into the physical one. Anything that is not recognized as abstract type will be kept in the
     * generated SQL.
     *
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become
     * 'varchar(255) not null'.
     *
     * @return string the SQL statement for changing the definition of a column.
     */
    public function alterColumn(string $table, string $column, string $type): string
    {
        $type = $this->getColumnType($type);

        return 'ALTER TABLE '
            . $this->getDb()->quoteTableName($table)
            . ' MODIFY '
            . $this->getDb()->quoteColumnName($column)
            . ' ' . $this->getColumnType($type);
    }

    /**
     * Builds a SQL statement for dropping an index.
     *
     * @param string $name the name of the index to be dropped. The name will be properly quoted by the method.
     * @param string $table the table whose index is to be dropped. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for dropping an index.
     */
    public function dropIndex(string $name, string $table): string
    {
        return 'DROP INDEX ' . $this->getDb()->quoteTableName($name);
    }

    public function resetSequence(string $tableName, $value = null): string
    {
        $tableSchema = $this->getDb()->getTableSchema($table);

        if ($tableSchema === null) {
            throw new InvalidArgumentException("Unknown table: $table");
        }
        if ($tableSchema->getSequenceName() === null) {
            throw new InvalidArgumentException("There is no sequence associated with table: $table");
        }

        if ($value !== null) {
            $value = (int) $value;
        } else {
            if (count($tableSchema->getPrimaryKey()) > 1) {
                throw new InvalidArgumentException("Can't reset sequence for composite primary key in table: $table");
            }
            /** use master connection to get the biggest PK value */
            $value = $this->getDb()->useMaster(static function (Connection $db) use ($tableSchema) {
                return $db->createCommand(
                    'SELECT MAX("' . $tableSchema->getPrimaryKey()[0] . '") FROM "' . $tableSchema->getName() . '"'
                )->queryScalar();
            }) + 1;
        }

        /**
         *  Oracle needs at least two queries to reset sequence (see adding transactions and/or use alter method to
         *  avoid grants' issue?)
         */
        $this->db->createCommand('DROP SEQUENCE "' . $tableSchema->getSequenceName() . '"')->execute();
        $this->db->createCommand(
            'CREATE SEQUENCE "' .
            $tableSchema->getSequenceName() .
            '" START WITH ' .
            $value .
            ' INCREMENT BY 1 NOMAXVALUE NOCACHE'
        )->execute();
    }

    public function addForeignKey(
        string $name,
        string $table,
        $columns,
        string $refTable,
        $refColumns,
        ?string $delete = null,
        ?string $update = null
    ): string {
        $sql = 'ALTER TABLE ' . $this->getDb()->quoteTableName($table)
            . ' ADD CONSTRAINT ' . $this->getDb()->quoteColumnName($name)
            . ' FOREIGN KEY (' . $this->buildColumns($columns) . ')'
            . ' REFERENCES ' . $this->getDb()->quoteTableName($refTable)
            . ' (' . $this->buildColumns($refColumns) . ')';

        if ($delete !== null) {
            $sql .= ' ON DELETE ' . $delete;
        }

        if ($update !== null) {
            throw new Exception('Oracle does not support ON UPDATE clause.');
        }

        return $sql;
    }

    protected function prepareInsertValues(string $table, $columns, array $params = []): array
    {
        [$names, $placeholders, $values, $params] = parent::prepareInsertValues($table, $columns, $params);

        if (!$columns instanceof Query && empty($names)) {
            $tableSchema = $this->getDb()->getSchema()->getTableSchema($table);

            if ($tableSchema !== null) {
                $columns = !empty($tableSchema->primaryKey)
                    ? $tableSchema->primaryKey : [reset($tableSchema->columns)->name];
                foreach ($columns as $name) {
                    $names[] = $this->getDb()->quoteColumnName($name);
                    $placeholders[] = 'DEFAULT';
                }
            }
        }

        return [$names, $placeholders, $values, $params];
    }

    /**
     * @see https://docs.oracle.com/cd/B28359_01/server.111/b28286/statements_9016.htm#SQLRF01606
     */
    public function upsert(string $table, $insertColumns, $updateColumns, array &$params = []): string
    {
        $constraints = [];

        /** @var Constraint[] $constraints */
        [$uniqueNames, $insertNames, $updateNames] = $this->prepareUpsertColumns(
            $table,
            $insertColumns,
            $updateColumns,
            $constraints
        );

        if (empty($uniqueNames)) {
            return $this->insert($table, $insertColumns, $params);
        }

        if ($updateNames === []) {
            /** there are no columns to update */
            $updateColumns = false;
        }

        $onCondition = ['or'];
        $quotedTableName = $this->getDb()->quoteTableName($table);

        foreach ($constraints as $constraint) {
            $constraintCondition = ['and'];
            foreach ($constraint->getColumnNames() as $name) {
                $quotedName = $this->getDb()->quoteColumnName($name);
                $constraintCondition[] = "$quotedTableName.$quotedName=\"EXCLUDED\".$quotedName";
            }

            $onCondition[] = $constraintCondition;
        }

        $on = $this->buildCondition($onCondition, $params);

        [, $placeholders, $values, $params] = $this->prepareInsertValues($table, $insertColumns, $params);

        if (!empty($placeholders)) {
            $usingSelectValues = [];
            foreach ($insertNames as $index => $name) {
                $usingSelectValues[$name] = new Expression($placeholders[$index]);
            }

            $usingSubQuery = (new Query($this->getDb()))
                ->select($usingSelectValues)
                ->from('DUAL');

            [$usingValues, $params] = $this->build($usingSubQuery, $params);
        }

        $mergeSql = 'MERGE INTO ' . $this->getDb()->quoteTableName($table) . ' '
            . 'USING (' . (isset($usingValues) ? $usingValues : ltrim($values, ' ')) . ') "EXCLUDED" '
            . "ON ($on)";

        $insertValues = [];
        foreach ($insertNames as $name) {
            $quotedName = $this->getDb()->quoteColumnName($name);

            if (strrpos($quotedName, '.') === false) {
                $quotedName = '"EXCLUDED".' . $quotedName;
            }

            $insertValues[] = $quotedName;
        }

        $insertSql = 'INSERT (' . implode(', ', $insertNames) . ')'
            . ' VALUES (' . implode(', ', $insertValues) . ')';

        if ($updateColumns === false) {
            return "$mergeSql WHEN NOT MATCHED THEN $insertSql";
        }

        if ($updateColumns === true) {
            $updateColumns = [];
            foreach ($updateNames as $name) {
                $quotedName = $this->getDb()->quoteColumnName($name);

                if (strrpos($quotedName, '.') === false) {
                    $quotedName = '"EXCLUDED".' . $quotedName;
                }
                $updateColumns[$name] = new Expression($quotedName);
            }
        }

        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);

        $updateSql = 'UPDATE SET ' . implode(', ', $updates);

        return "$mergeSql WHEN MATCHED THEN $updateSql WHEN NOT MATCHED THEN $insertSql";
    }

    /**
     * Generates a batch INSERT SQL statement.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->batchInsert('user', ['name', 'age'], [
     *     ['Tom', 30],
     *     ['Jane', 20],
     *     ['Linda', 25],
     * ]);
     * ```
     *
     * Note that the values in each row must match the corresponding column names.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column names.
     * @param array|\Generator $rows the rows to be batch inserted into the table.
     *
     * @return string the batch INSERT SQL statement.
     */
    public function batchInsert(string $table, $columns, $rows, array &$params = []): string
    {
        if (empty($rows)) {
            return '';
        }

        $schema = $this->getDb()->getSchema();

        if (($tableSchema = $schema->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->getColumns();
        } else {
            $columnSchemas = [];
        }

        $values = [];

        foreach ($rows as $row) {
            $vs = [];
            foreach ($row as $i => $value) {
                if (isset($columns[$i], $columnSchemas[$columns[$i]])) {
                    $value = $columnSchemas[$columns[$i]]->dbTypecast($value);
                }

                if (is_string($value)) {
                    $value = $schema->quoteValue($value);
                } elseif (is_float($value)) {
                    /* ensure type cast always has . as decimal separator in all locales */
                    $value = NumericHelper::normalize((string) $value);
                } elseif ($value === false) {
                    $value = 0;
                } elseif ($value === null) {
                    $value = '0';
                } elseif ($value instanceof ExpressionInterface) {
                    $value = $this->buildExpression($value, $params);
                }

                $vs[] = $value;
            }

            $values[] = '(' . implode(', ', $vs) . ')';
        }

        if (empty($values)) {
            return '';
        }

        foreach ($columns as $i => $name) {
            $columns[$i] = $schema->quoteColumnName($name);
        }

        $tableAndColumns = ' INTO ' . $schema->quoteTableName($table)
            . ' (' . implode(', ', $columns) . ') VALUES ';

        return 'INSERT ALL ' . $tableAndColumns . implode($tableAndColumns, $values) . ' SELECT 1 FROM SYS.DUAL';
    }

    public function selectExists(string $rawSql): string
    {
        return 'SELECT CASE WHEN EXISTS(' . $rawSql . ') THEN 1 ELSE 0 END FROM DUAL';
    }

    public function dropCommentFromColumn(string $table, string $column): string
    {
        return 'COMMENT ON COLUMN ' . $this->getDb()->quoteTableName($table) . '.' . $this->getDb()->quoteColumnName($column) . " IS ''";
    }

    public function dropCommentFromTable(string $table): string
    {
        return 'COMMENT ON TABLE ' . $this->getDb()->quoteTableName($table) . " IS ''";
    }
}
