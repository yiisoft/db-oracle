<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Oracle\Builder\InConditionBuilder;
use Yiisoft\Db\Oracle\Builder\LikeConditionBuilder;
use Yiisoft\Db\Query\Conditions\InCondition;
use Yiisoft\Db\Query\Conditions\LikeCondition;
use Yiisoft\Db\Query\DQLQueryBuilder as AbstractDQLQueryBuilder;
use Yiisoft\Db\Query\QueryBuilderInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_merge;
use function implode;

final class DQLQueryBuilder extends AbstractDQLQueryBuilder
{
    public function __construct(
        QueryBuilderInterface $queryBuilder,
        QuoterInterface $quoter,
        SchemaInterface $schema
    ) {
        parent::__construct($queryBuilder, $quoter, $schema);
    }

    public function buildOrderByAndLimit(string $sql, array $orderBy, $limit, $offset, array &$params = []): string
    {
        $orderByString = $this->buildOrderBy($orderBy, $params);

        if ($orderByString !== '') {
            $sql .= $this->separator . $orderByString;
        }

        $filters = [];

        if ($this->hasOffset($offset)) {
            $filters[] = 'rowNumId > ' . (string) $offset;
        }

        if ($this->hasLimit($limit)) {
            $filters[] = 'rownum <= ' . (string) $limit;
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

    protected function defaultExpressionBuilders(): array
    {
        return array_merge(parent::defaultExpressionBuilders(), [
            InCondition::class => InConditionBuilder::class,
            LikeCondition::class => LikeConditionBuilder::class,
        ]);
    }
}
