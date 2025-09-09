<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use InvalidArgumentException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder;

use function array_combine;
use function array_fill;
use function array_key_first;
use function array_map;
use function implode;
use function count;

/**
 * Implements a DML (Data Manipulation Language) SQL statements for Oracle Server.
 */
final class DMLQueryBuilder extends AbstractDMLQueryBuilder
{
    public function insertBatch(string $table, iterable $rows, array $columns = [], array &$params = []): string
    {
        if (!is_array($rows)) {
            $rows = $this->prepareTraversable($rows);
        }

        if (empty($rows)) {
            return '';
        }

        $columns = $this->extractColumnNames($rows, $columns);
        $values = $this->prepareBatchInsertValues($table, $rows, $columns, $params);

        if (empty($values)) {
            return '';
        }

        $query = 'INSERT INTO ' . $this->quoter->quoteTableName($table);

        if (count($columns) > 0) {
            $quotedColumnNames = array_map($this->quoter->quoteColumnName(...), $columns);

            $query .= ' (' . implode(', ', $quotedColumnNames) . ')';
        }

        return $query . "\nSELECT " . implode(" FROM DUAL UNION ALL\nSELECT ", $values) . ' FROM DUAL';
    }

    public function insertReturningPks(string $table, array|QueryInterface $columns, array &$params = []): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by Oracle.');
    }

    public function update(
        string $table,
        array $columns,
        array|string|ExpressionInterface $condition,
        array|string|ExpressionInterface|null $from = null,
        array &$params = []
    ): string {
        if ($from !== null) {
            throw new NotSupportedException('Oracle does not support UPDATE with FROM clause.');
        }
        return parent::update($table, $columns, $condition, null, $params);
    }

    /**
     * @link https://docs.oracle.com/cd/B28359_01/server.111/b28286/statements_9016.htm#SQLRF01606
     */
    public function upsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array &$params = [],
    ): string {
        $constraints = [];

        [$uniqueNames, $insertNames, $updateNames] = $this->prepareUpsertColumns(
            $table,
            $insertColumns,
            $updateColumns,
            $constraints
        );

        if (empty($uniqueNames)) {
            return $this->insert($table, $insertColumns, $params);
        }

        $onCondition = ['or'];
        $quotedTableName = $this->quoter->quoteTableName($table);

        foreach ($constraints as $constraint) {
            $columnNames = $constraint->columnNames;
            $constraintCondition = ['and'];

            foreach ($columnNames as $name) {
                $quotedName = $this->quoter->quoteColumnName($name);
                $constraintCondition[] = "$quotedTableName.$quotedName=EXCLUDED.$quotedName";
            }

            $onCondition[] = $constraintCondition;
        }

        $on = $this->queryBuilder->buildCondition($onCondition, $params);

        [, $placeholders, $values, $params] = $this->prepareInsertValues($table, $insertColumns, $params);

        if (!empty($placeholders)) {
            $values = $this->buildSimpleSelect(array_combine($insertNames, $placeholders)) . ' FROM "DUAL"';
        }

        $insertValues = [];
        $quotedInsertNames = array_map($this->quoter->quoteColumnName(...), $insertNames);

        foreach ($quotedInsertNames as $quotedName) {
            $insertValues[] = 'EXCLUDED.' . $quotedName;
        }

        $mergeSql = 'MERGE INTO ' . $quotedTableName . ' USING (' . $values . ') EXCLUDED ON (' . $on . ')';
        $insertSql = 'INSERT (' . implode(', ', $quotedInsertNames) . ')'
            . ' VALUES (' . implode(', ', $insertValues) . ')';

        if (empty($updateColumns) || $updateNames === []) {
            /** there are no columns to update */
            return "$mergeSql WHEN NOT MATCHED THEN $insertSql";
        }

        $updates = $this->prepareUpsertSets($table, $updateColumns, $updateNames, $params);
        $updateSql = 'UPDATE SET ' . implode(', ', $updates);

        return "$mergeSql WHEN MATCHED THEN $updateSql WHEN NOT MATCHED THEN $insertSql";
    }

    public function upsertReturning(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array|null $returnColumns = null,
        array &$params = [],
    ): string {
        throw new NotSupportedException(__METHOD__ . '() is not supported by Oracle.');
    }

    protected function prepareInsertValues(string $table, array|QueryInterface $columns, array $params = []): array
    {
        if (empty($columns)) {
            $names = [];
            $placeholders = [];
            $tableSchema = $this->schema->getTableSchema($table);

            if ($tableSchema !== null) {
                if (!empty($tableSchema->getPrimaryKey())) {
                    $names = $tableSchema->getPrimaryKey();
                } else {
                    /**
                     * @psalm-suppress PossiblyNullArgument
                     * @var string[] $names
                     */
                    $names = [array_key_first($tableSchema->getColumns())];
                }

                $placeholders = array_fill(0, count($names), 'DEFAULT');
            }

            return [$names, $placeholders, '', $params];
        }

        return parent::prepareInsertValues($table, $columns, $params);
    }

    public function resetSequence(string $table, int|string|null $value = null): string
    {
        $tableSchema = $this->schema->getTableSchema($table);

        if ($tableSchema === null) {
            throw new InvalidArgumentException("Table not found: '$table'.");
        }

        $sequenceName = $tableSchema->getSequenceName();

        if ($sequenceName === null) {
            throw new InvalidArgumentException("There is not sequence associated with table '$table'.");
        }

        if ($value === null && count($tableSchema->getPrimaryKey()) > 1) {
            throw new InvalidArgumentException("Can't reset sequence for composite primary key in table: $table");
        }

        /**
         * Oracle needs at least many queries to reset a sequence (see adding transactions and/or use an alter method to
         * avoid grant issue?)
         */
        return 'declare
    lastSeq number' . ($value !== null ? (' := ' . $value) : '') . ';
begin' . ($value === null ? '
    SELECT MAX("' . $tableSchema->getPrimaryKey()[0] . '") + 1 INTO lastSeq FROM "' . $tableSchema->getName() . '";' : '') . '
    if lastSeq IS NULL then lastSeq := 1; end if;
    execute immediate \'DROP SEQUENCE "' . $sequenceName . '"\';
    execute immediate \'CREATE SEQUENCE "' . $sequenceName . '" START WITH \' || lastSeq || \' INCREMENT BY 1 NOMAXVALUE NOCACHE\';
end;';
    }
}
