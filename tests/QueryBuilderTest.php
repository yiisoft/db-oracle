<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\Query\QueryBuilder;
use Yiisoft\Db\TestUtility\TestQueryBuilderTrait;

/**
 * @group oracle
 */
final class QueryBuilderTest extends TestCase
{
    use TestQueryBuilderTrait;

    protected function getQueryBuilder(bool $reset = false): QueryBuilder
    {
        return new QueryBuilder($this->getConnection($reset));
    }
}
