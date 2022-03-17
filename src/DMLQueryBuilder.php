<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use InvalidArgumentException;
use JsonException;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Oracle\PDO\QueryBuilderPDOOracle;
use Yiisoft\Db\Query\DMLQueryBuilder as AbstractDMLQueryBuilder;
use Yiisoft\Db\Query\QueryBuilderInterface;
use Yiisoft\Db\Query\QueryInterface;

final class DMLQueryBuilder extends AbstractDMLQueryBuilder
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
        parent::__construct($queryBuilder);
    }

    /**
     * @link https://docs.oracle.com/cd/B28359_01/server.111/b28286/statements_9016.htm#SQLRF01606
     *
     * @param string $table
     * @param QueryInterface|array $insertColumns
     * @param array|bool $updateColumns
     * @param array $params
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|JsonException|NotSupportedException
     *
     * @return string
     */
    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        array|bool $updateColumns,
        array &$params = []
    ): string {
        $usingValues = null;
        $constraints = [];

        /** @var Constraint[] $constraints */
        [$uniqueNames, $insertNames, $updateNames] = $this->queryBuilder->prepareUpsertColumns(
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
        $quotedTableName = $this->queryBuilder->quoter()->quoteTableName($table);

        foreach ($constraints as $constraint) {
            $columnNames = $constraint->getColumnNames() ?? [];
            $constraintCondition = ['and'];
            /** @psalm-var string[] $columnNames */
            foreach ($columnNames as $name) {
                $quotedName = $this->queryBuilder->quoter()->quoteColumnName($name);
                $constraintCondition[] = "$quotedTableName.$quotedName=\"EXCLUDED\".$quotedName";
            }

            $onCondition[] = $constraintCondition;
        }

        $on = $this->queryBuilder->buildCondition($onCondition, $params);
        /** @psalm-var string[] $placeholders */
        [, $placeholders, $values, $params] = $this->queryBuilder->prepareInsertValues($table, $insertColumns, $params);

        if (!empty($placeholders)) {
            $usingSelectValues = [];
            /** @psalm-var string[] $insertNames */
            foreach ($insertNames as $index => $name) {
                $usingSelectValues[$name] = new Expression($placeholders[$index]);
            }

            /** @var QueryBuilderPDOOracle $usingSubQuery */
            $usingSubQuery = $this->queryBuilder;
            $query = $usingSubQuery->query()->select($usingSelectValues)->from('DUAL');
            [$usingValues, $params] = $this->queryBuilder->build($query, (array) $params);
        }

        $insertValues = [];
        $mergeSql = 'MERGE INTO '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' '
            . 'USING (' . ($usingValues ?? ltrim((string) $values, ' '))
            . ') "EXCLUDED" '
            . "ON ($on)";

        /** @psalm-var string[] $insertNames */
        foreach ($insertNames as $name) {
            $quotedName = $this->queryBuilder->quoter()->quoteColumnName($name);

            if (strrpos($quotedName, '.') === false) {
                $quotedName = '"EXCLUDED".' . $quotedName;
            }

            $insertValues[] = $quotedName;
        }

        $insertSql = 'INSERT (' . implode(', ', $insertNames) . ')' . ' VALUES (' . implode(', ', $insertValues) . ')';

        if ($updateColumns === false) {
            return "$mergeSql WHEN NOT MATCHED THEN $insertSql";
        }

        if ($updateColumns === true) {
            $updateColumns = [];
            /** @psalm-var string[] $updateNames */
            foreach ($updateNames as $name) {
                $quotedName = $this->queryBuilder->quoter()->quoteColumnName($name);

                if (strrpos($quotedName, '.') === false) {
                    $quotedName = '"EXCLUDED".' . $quotedName;
                }
                $updateColumns[$name] = new Expression($quotedName);
            }
        }

        /** @psalm-var string[] $updates */
        [$updates, $params] = $this->queryBuilder->prepareUpdateSets($table, $updateColumns, (array) $params);
        $updateSql = 'UPDATE SET ' . implode(', ', $updates);

        return "$mergeSql WHEN MATCHED THEN $updateSql WHEN NOT MATCHED THEN $insertSql";
    }
}
