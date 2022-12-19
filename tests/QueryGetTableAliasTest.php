<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractQueryGetTableAliasTest;

/**
 * @group oracle
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryGetTableAliasTest extends AbstractQueryGetTableAliasTest
{
    use TestTrait;
}
