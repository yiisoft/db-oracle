<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonQueryTest;

/**
 * @group oracle
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryTest extends CommonQueryTest
{
    use TestTrait;
}
