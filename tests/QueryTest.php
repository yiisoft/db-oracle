<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\Oracle\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Common\CommonQueryTest;

/**
 * @group oracle
 */
final class QueryTest extends CommonQueryTest
{
    use IntegrationTestTrait;

    /**
     * Ensure no ambiguous column error occurs on indexBy with JOIN.
     *
     * @link https://github.com/yiisoft/yii2/issues/13859
     */
    public function testAmbiguousColumnIndexBy(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $selectExpression = "[[customer]].[[name]] || ' in ' || [[p]].[[description]] name";

        $result = (new Query($db))
            ->select([$selectExpression])
            ->from('customer')
            ->innerJoin('profile p', '[[customer]].[[profile_id]] = [[p]].[[id]]')
            ->indexBy('id')
            ->column();

        $this->assertSame([1 => 'user1 in profile customer 1', 3 => 'user3 in profile customer 3'], $result);
    }
}
