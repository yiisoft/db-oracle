<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Oracle\ServerInfo;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonPdoConnectionTest;

/**
 * @group oracle
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PdoConnectionTest extends CommonPdoConnectionTest
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

        $this->assertSame('7', $db->getLastInsertId('item_SEQ'));

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

        $db->getLastInsertId();
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

        $this->assertNotEquals($db1->getLastInsertId('profile_SEQ'), $db2->getLastInsertId('profile_SEQ'));
        $this->assertNotEquals($db2->getLastInsertId('profile_SEQ'), $db1->getLastInsertId('profile_SEQ'));

        $db1->close();
        $db2->close();
    }

    public function testGetServerInfo(): void
    {
        $db = $this->getConnection();
        $serverInfo = $db->getServerInfo();

        $this->assertInstanceOf(ServerInfo::class, $serverInfo);

        $dbTimezone = $serverInfo->getTimezone();

        $this->assertSame(6, strlen($dbTimezone));

        $db->createCommand("ALTER SESSION SET TIME_ZONE = '+06:15'")->execute();

        $this->assertSame($dbTimezone, $serverInfo->getTimezone());
        $this->assertNotSame($dbTimezone, $serverInfo->getTimezone(true));
        $this->assertSame('+06:15', $serverInfo->getTimezone());

        $db->close();
    }
}
