<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonConnectionPDOTest;

/**
 * @group oracle
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ConnectionPDOTest extends CommonConnectionPDOTest
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidCallException
     * @throws Throwable
     */
    public function testGetLastInsertID(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert('item', ['name' => 'Yii2 starter', 'category_id' => 1])->execute();
        $command->insert('item', ['name' => 'Yii3 starter', 'category_id' => 1])->execute();

        $this->assertSame('7', $db->getLastInsertID('item_SEQ'));

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidCallException
     * @throws Throwable
     */
    public function testGetLastInsertIDWithException(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert('item', ['name' => 'Yii2 starter', 'category_id' => 1])->execute();
        $command->insert('item', ['name' => 'Yii3 starter', 'category_id' => 1])->execute();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Oracle not support lastInsertId without sequence name.');

        $db->getLastInsertID();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidCallException
     * @throws Throwable
     */
    public function testGetLastInsertIdWithTwoConnection()
    {
        $db1 = $this->getConnection();
        $db2 = $this->getConnection();

        $sql = 'INSERT INTO {{profile}}([[description]]) VALUES (\'non duplicate1\')';
        $db1->createCommand($sql)->execute();

        $sql = 'INSERT INTO {{profile}}([[description]]) VALUES (\'non duplicate2\')';
        $db2->createCommand($sql)->execute();

        $this->assertNotEquals($db1->getLastInsertID('profile_SEQ'), $db2->getLastInsertID('profile_SEQ'));
        $this->assertNotEquals($db2->getLastInsertID('profile_SEQ'), $db1->getLastInsertID('profile_SEQ'));

        $db1->close();
        $db2->close();
    }
}
