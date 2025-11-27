<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\Oracle\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Common\CommonBatchQueryResultTest;

/**
 * @group oracle
 */
final class BatchQueryResultTest extends CommonBatchQueryResultTest
{
    use IntegrationTestTrait;
}
