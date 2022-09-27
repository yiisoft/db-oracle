<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PDO;
use Yiisoft\Cache\CacheKeyNormalizer;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\TestSupport\TestConnectionTrait;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * @group oracle
 */
final class ConnectionTest extends TestCase
{
    use TestConnectionTrait;

    public function testGetDriverName(): void
    {
        $db = $this->getConnection();
        $this->assertEquals('oci', $db->getDriver()->getDriverName());
    }

    public function testSerialize()
    {
        $db = $this->getConnection();
        $db->open();
        $serialized = serialize($db);
        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(ConnectionInterface::class, $unserialized);
        $this->assertEquals(123, $unserialized->createCommand('SELECT 123 FROM DUAL')->queryScalar());
    }

    public function testQuoteTableName()
    {
        $db = $this->getConnection();
        $quoter = $db->getQuoter();

        $this->assertEquals('"table"', $quoter->quoteTableName('table'));
        $this->assertEquals('"table"', $quoter->quoteTableName('"table"'));
        $this->assertEquals('"schema"."table"', $quoter->quoteTableName('schema.table'));
        $this->assertEquals('"schema"."table"', $quoter->quoteTableName('schema."table"'));
        $this->assertEquals('"schema"."table"', $quoter->quoteTableName('"schema"."table"'));
        $this->assertEquals('{{table}}', $quoter->quoteTableName('{{table}}'));
        $this->assertEquals('(table)', $quoter->quoteTableName('(table)'));
    }

    public function testQuoteColumnName()
    {
        $db = $this->getConnection();
        $quoter = $db->getQuoter();

        $this->assertEquals('"column"', $quoter->quoteColumnName('column'));
        $this->assertEquals('"column"', $quoter->quoteColumnName('"column"'));
        $this->assertEquals('[[column]]', $quoter->quoteColumnName('[[column]]'));
        $this->assertEquals('{{column}}', $quoter->quoteColumnName('{{column}}'));
        $this->assertEquals('(column)', $quoter->quoteColumnName('(column)'));

        $this->assertEquals('"column"', $quoter->quoteSql('[[column]]'));
        $this->assertEquals('"column"', $quoter->quoteSql('{{column}}'));
    }

    public function testQuoteFullColumnName()
    {
        $db = $this->getConnection();
        $quoter = $db->getQuoter();

        $this->assertEquals('"table"."column"', $quoter->quoteColumnName('table.column'));
        $this->assertEquals('"table"."column"', $quoter->quoteColumnName('table."column"'));
        $this->assertEquals('"table"."column"', $quoter->quoteColumnName('"table".column'));
        $this->assertEquals('"table"."column"', $quoter->quoteColumnName('"table"."column"'));

        $this->assertEquals('[[table.column]]', $quoter->quoteColumnName('[[table.column]]'));
        $this->assertEquals('{{table}}."column"', $quoter->quoteColumnName('{{table}}.column'));
        $this->assertEquals('{{table}}."column"', $quoter->quoteColumnName('{{table}}."column"'));
        $this->assertEquals('{{table}}.[[column]]', $quoter->quoteColumnName('{{table}}.[[column]]'));
        $this->assertEquals('{{%table}}."column"', $quoter->quoteColumnName('{{%table}}.column'));
        $this->assertEquals('{{%table}}."column"', $quoter->quoteColumnName('{{%table}}."column"'));

        $this->assertEquals('"table"."column"', $quoter->quoteSql('[[table.column]]'));
        $this->assertEquals('"table"."column"', $quoter->quoteSql('{{table}}.[[column]]'));
        $this->assertEquals('"table"."column"', $quoter->quoteSql('{{table}}."column"'));
        $this->assertEquals('"table"."column"', $quoter->quoteSql('{{%table}}.[[column]]'));
        $this->assertEquals('"table"."column"', $quoter->quoteSql('{{%table}}."column"'));
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
        $result = $db->transaction(static function (ConnectionInterface $db) {
            $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
            return true;
        }, TransactionInterface::READ_COMMITTED);
        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction shortcut'"
        )->queryScalar();
        $this->assertEquals(1, $profilesCount, 'profile should be inserted in transaction shortcut');
    }

    public function testQuoteValue()
    {
        $db = $this->getConnection();
        $quoter = $db->getQuoter();

        $this->assertEquals(123, $quoter->quoteValue(123));
        $this->assertEquals("'string'", $quoter->quoteValue('string'));
        $this->assertEquals("'It''s interesting'", $quoter->quoteValue("It's interesting"));
    }

    /**
     * Test whether slave connection is recovered when call `getSlavePdo()` after `close()`.
     *
     * {@see https://github.com/yiisoft/yii2/issues/14165}
     */
    public function testGetPdoAfterClose(): void
    {
        $this->markTestSkipped('Only for master/slave');

        $db = $this->getConnection();

        $db->setSlave('1', $this->getConnection());
        $this->assertNotNull($db->getSlavePdo(false));

        $db->close();

        $masterPdo = $db->getMasterPdo();
        $this->assertNotFalse($masterPdo);
        $this->assertNotNull($masterPdo);

        $slavePdo = $db->getSlavePdo(false);
        $this->assertNotFalse($slavePdo);
        $this->assertNotNull($slavePdo);
        $this->assertNotSame($masterPdo, $slavePdo);
    }

    public function testServerStatusCacheWorks(): void
    {
        $db = null;
        $this->markTestSkipped('Only for master/slave');

        $db = $this->getConnection();
        $cacheKeyNormalizer = new CacheKeyNormalizer();

        $db->setMaster('1', $this->getConnection());
        $db->setShuffleMasters(false);

        $cacheKey = $cacheKeyNormalizer->normalize(
            ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', $db->getDriver()->getDsn()]
        );
        $this->assertFalse($this->cache->psr()->has($cacheKey));

        $db->open();
        $this->assertFalse(
            $this->cache->psr()->has($cacheKey),
            'Connection was successful – cache must not contain information about this DSN'
        );

        $db->close();

        $cacheKey = $cacheKeyNormalizer->normalize(
            ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', 'host:invalid']
        );

        $db->setMaster('1', $this->getConnection(false, 'host:invalid'));
        $db->setShuffleMasters(true);

        try {
            $db->open();
        } catch (InvalidConfigException) {
        }

        $this->assertTrue(
            $this->cache->psr()->has($cacheKey),
            'Connection was not successful – cache must contain information about this DSN'
        );

        $db->close();
    }

    public function testServerStatusCacheCanBeDisabled(): void
    {
        $db = null;
        $this->markTestSkipped('Only for master/slave');

        $db = $this->getConnection();
        $cacheKeyNormalizer = new CacheKeyNormalizer();

        $db->setMaster('1', $this->getConnection());
        $this->schemaCache->setEnable(false);
        $db->setShuffleMasters(false);

        $cacheKey = $cacheKeyNormalizer->normalize(
            ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially::', $db->getDriver()->getDsn()]
        );
        $this->assertFalse($this->cache->psr()->has($cacheKey));

        $db->open();
        $this->assertFalse($this->cache->psr()->has($cacheKey), 'Caching is disabled');

        $db->close();

        $cacheKey = $cacheKeyNormalizer->normalize(
            ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', 'host:invalid']
        );
        $db->setMaster('1', $this->getConnection(false, 'host:invalid'));

        try {
            $db->open();
        } catch (InvalidConfigException) {
        }

        $this->assertFalse($this->cache->psr()->has($cacheKey), 'Caching is disabled');

        $db->close();
    }

    public function testSettingDefaultAttributes(): void
    {
        $db = $this->getConnection();
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $db->getActivePDO()->getAttribute(PDO::ATTR_ERRMODE));
        $db->close();
    }

    public function testLastInsertIdTwoConnection()
    {
        $db1 = $this->getConnection();
        $db2 = $this->getConnection();

        $sql = 'INSERT INTO {{profile}}([[description]]) VALUES (\'non duplicate1\')';
        $db1->createCommand($sql)->execute();

        $sql = 'INSERT INTO {{profile}}([[description]]) VALUES (\'non duplicate2\')';
        $db2->createCommand($sql)->execute();

        $this->assertNotEquals($db1->getLastInsertID('profile_SEQ'), $db2->getLastInsertID('profile_SEQ'));
        $this->assertNotEquals($db2->getLastInsertID('profile_SEQ'), $db1->getLastInsertID('profile_SEQ'));
    }
}
