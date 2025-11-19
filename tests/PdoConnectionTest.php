<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use InvalidArgumentException;
use Yiisoft\Db\Oracle\ServerInfo;
use Yiisoft\Db\Oracle\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Common\CommonPdoConnectionTest;

use function strlen;

/**
 * @group oracle
 */
final class PdoConnectionTest extends CommonPdoConnectionTest
{
    use IntegrationTestTrait;

    public function testGetLastInsertID(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $command = $db->createCommand();
        $command->insert('item', ['name' => 'Yii2 starter', 'category_id' => 1])->execute();
        $command->insert('item', ['name' => 'Yii3 starter', 'category_id' => 1])->execute();

        $this->assertSame('7', $db->getLastInsertId('item_SEQ'));

        $db->close();
    }

    public function testGetLastInsertIDWithException(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $command = $db->createCommand();
        $command->insert('item', ['name' => 'Yii2 starter', 'category_id' => 1])->execute();
        $command->insert('item', ['name' => 'Yii3 starter', 'category_id' => 1])->execute();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Oracle not support lastInsertId without sequence name.');

        $db->getLastInsertId();
    }

    public function testGetLastInsertIdWithTwoConnection()
    {
        $db1 = $this->createConnection();
        $db2 = $this->createConnection();

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
        $db = $this->createConnection();
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
