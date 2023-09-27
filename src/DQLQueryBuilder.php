<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Oracle\Builder\InConditionBuilder;
use Yiisoft\Db\Oracle\Builder\LikeConditionBuilder;
use Yiisoft\Db\QueryBuilder\AbstractDQLQueryBuilder;
use Yiisoft\Db\QueryBuilder\Condition\InCondition;
use Yiisoft\Db\QueryBuilder\Condition\LikeCondition;

use function array_merge;
use function implode;

/**
 * Implements a DQL (Data Query Language) SQL statements for Oracle Server.
 */
final class DQLQueryBuilder extends AbstractDQLQueryBuilder
{
    public function buildOrderByAndLimit(
        string $sql,
        array $orderBy,
        ExpressionInterface|int|null $limit,
        ExpressionInterface|int|null $offset,
        array &$params = []
    ): string {
        $orderByString = $this->buildOrderBy($orderBy, $params);

        if ($orderByString !== '') {
            $sql .= $this->separator . $orderByString;
        }

        $filters = [];

        if ($this->hasOffset($offset)) {
            $filters[] = 'rowNumId > ' .
                ($offset instanceof ExpressionInterface ? $this->buildExpression($offset) : (string)$offset);
        }

        if ($this->hasLimit($limit)) {
            $filters[] = 'rownum <= ' .
                ($limit instanceof ExpressionInterface ? $this->buildExpression($limit) : (string)$limit);
        }

        if (empty($filters)) {
            return $sql;
        }

        $filter = implode(' AND ', $filters);
        return <<<SQL
        WITH USER_SQL AS ($sql), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
        SELECT * FROM PAGINATION WHERE $filter
        SQL;
    }

    public function selectExists(string $rawSql): string
    {
        return 'SELECT CASE WHEN EXISTS(' . $rawSql . ') THEN 1 ELSE 0 END FROM DUAL';
    }

    public function buildFrom(array|null $tables, array &$params): string
    {
        if (empty($tables)) {
            return 'FROM DUAL';
        }

        return parent::buildFrom($tables, $params);
    }

    protected function defaultExpressionBuilders(): array
    {
        return array_merge(
            parent::defaultExpressionBuilders(),
            [
                InCondition::class => InConditionBuilder::class,
                LikeCondition::class => LikeConditionBuilder::class,
            ],
        );
    }
}
