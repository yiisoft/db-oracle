<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\Oracle\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Common\CommonQueryGetTableAliasTest;

/**
 * @group oracle
 */
final class QueryGetTableAliasTest extends CommonQueryGetTableAliasTest
{
    use IntegrationTestTrait;
}
