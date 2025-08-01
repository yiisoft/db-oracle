<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Oracle\Builder\InBuilder;
use Yiisoft\Db\Oracle\Builder\LikeBuilder;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\AbstractDQLQueryBuilder;
use Yiisoft\Db\QueryBuilder\Condition\In;
use Yiisoft\Db\QueryBuilder\Condition\Like;

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

        if (!empty($offset)) {
            $filters[] = 'rowNumId > ' .
                ($offset instanceof ExpressionInterface ? $this->buildExpression($offset) : (string) $offset);
        }

        if ($limit !== null) {
            $filters[] = 'rownum <= ' .
                ($limit instanceof ExpressionInterface ? $this->buildExpression($limit) : (string) $limit);
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
        return 'SELECT CASE WHEN EXISTS(' . $rawSql . ') THEN 1 ELSE 0 END AS "0" FROM DUAL';
    }

    public function buildFrom(array|null $tables, array &$params): string
    {
        if (empty($tables)) {
            return 'FROM DUAL';
        }

        return parent::buildFrom($tables, $params);
    }

    public function buildWithQueries(array $withs, array &$params): string
    {
        /** @psalm-var array{query:string|Query, alias:ExpressionInterface|string, recursive:bool}[] $withs */
        foreach ($withs as &$with) {
            $with['recursive'] = false;
        }

        return parent::buildWithQueries($withs, $params);
    }

    protected function defaultExpressionBuilders(): array
    {
        return [
            ...parent::defaultExpressionBuilders(),
            In::class => InBuilder::class,
            Like::class => LikeBuilder::class,
        ];
    }
}
