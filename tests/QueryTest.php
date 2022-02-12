<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\TestSupport\TestQueryTrait;

/**
 * @group oracle
 */
final class QueryTest extends TestCase
{
    use TestQueryTrait;

    public function testUnion(): void
    {
        $this->markTestSkipped('Unsupported use of WITH clause in Oracle.');
    }
}
