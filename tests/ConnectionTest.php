<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PDO;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonConnectionTest;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * @group oracle
 */
final class ConnectionTest extends CommonConnectionTest
{
    use TestTrait;

    public function testSerialize()
    {
        $db = $this->getConnection();

        $db->open();
        $serialized = serialize($db);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(ConnectionInterface::class, $unserialized);
        $this->assertSame('123', $unserialized->createCommand('SELECT 123 FROM DUAL')->queryScalar());
    }

    public function testSettingDefaultAttributes(): void
    {
        $db = $this->getConnection();

        $this->assertSame(PDO::ERRMODE_EXCEPTION, $db->getActivePDO()->getAttribute(PDO::ATTR_ERRMODE));

        $db->close();
    }

    public function testTransactionIsolation()
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
    }

    public function testTransactionShortcutCustom()
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
    }
}
