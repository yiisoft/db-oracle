<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PDO;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Oracle\Column\ColumnBuilder;
use Yiisoft\Db\Oracle\Column\ColumnFactory;
use Yiisoft\Db\Oracle\Connection;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonConnectionTest;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * @group oracle
 */
final class ConnectionTest extends CommonConnectionTest
{
    use TestTrait;

    public function testSerialize(): void
    {
        $db = $this->getConnection();

        $db->open();
        $serialized = serialize($db);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(ConnectionInterface::class, $unserialized);
        $this->assertSame('123', $unserialized->createCommand('SELECT 123 FROM DUAL')->queryScalar());

        $db->close();
    }

    public function testSettingDefaultAttributes(): void
    {
        $db = $this->getConnection();

        $this->assertSame(PDO::ERRMODE_EXCEPTION, $db->getActivePDO()?->getAttribute(PDO::ATTR_ERRMODE));

        $db->close();
    }

    public function testTransactionIsolation(): void
    {
        $db = $this->getConnection();

        $transaction = $db->beginTransaction(TransactionInterface::READ_COMMITTED);
        $transaction->commit();

        /* should not be any exception so far */
        $this->assertTrue(true);

        $transaction = $db->beginTransaction(TransactionInterface::SERIALIZABLE);
        $transaction->commit();

        /* should not be any exception so far */
        $this->assertTrue(true);

        $db->close();
    }

    public function testTransactionShortcutCustom(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $this->assertTrue(
            $db->transaction(
                static function (ConnectionInterface $db) {
                    $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();

                    return true;
                },
                TransactionInterface::READ_COMMITTED,
            ),
            'transaction shortcut valid value should be returned from callback',
        );

        $this->assertSame(
            '1',
            $command->setSql(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction shortcut'
                SQL,
            )->queryScalar(),
            'profile should be inserted in transaction shortcut',
        );

        $db->close();
    }

    public function testSerialized(): void
    {
        $connection = $this->getConnection();
        $connection->open();
        $serialized = serialize($connection);
        $this->assertNotNull($connection->getPDO());

        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(PdoConnectionInterface::class, $unserialized);
        $this->assertNull($unserialized->getPDO());
        $this->assertEquals(123, $unserialized->createCommand('SELECT 123 FROM DUAL')->queryScalar());
        $this->assertNotNull($connection->getPDO());
    }

    public function getColumnBuilderClass(): void
    {
        $db = $this->getConnection();

        $this->assertSame(ColumnBuilder::class, $db->getColumnBuilderClass());

        $db->close();
    }

    public function testGetColumnFactory(): void
    {
        $db = $this->getConnection();

        $this->assertInstanceOf(ColumnFactory::class, $db->getColumnFactory());

        $db->close();
    }

    public function testUserDefinedColumnFactory(): void
    {
        $columnFactory = new ColumnFactory();

        $db = new Connection($this->getDriver(), DbHelper::getSchemaCache(), $columnFactory);

        $this->assertSame($columnFactory, $db->getColumnFactory());

        $db->close();
    }
}
