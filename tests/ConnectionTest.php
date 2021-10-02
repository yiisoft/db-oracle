<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PDO;
use Yiisoft\Cache\CacheKeyNormalizer;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Oracle\Connection;
use Yiisoft\Db\TestUtility\TestConnectionTrait;
use Yiisoft\Db\Transaction\Transaction;

/**
 * @group oracle
 */
final class ConnectionTest extends TestCase
{
    use TestConnectionTrait;

    public function testConstruct(): void
    {
        $db = $this->getConnection();

        $this->assertEquals(self::DB_DSN, $db->getDsn());
    }

    public function testGetDriverName(): void
    {
        $db = $this->getConnection();

        $this->assertEquals('oci', $db->getDriverName());
    }

    public function testOpenClose(): void
    {
        $db = $this->getConnection();

        $this->assertFalse($db->isActive());
        $this->assertNull($db->getPDO());

        $db->open();

        $this->assertTrue($db->isActive());
        $this->assertInstanceOf(PDO::class, $db->getPDO());

        $db->close();

        $this->assertFalse($db->isActive());
        $this->assertNull($db->getPDO());

        $db = $this->createConnection('unknown::memory:');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('could not find driver');

        $db->open();
    }

    public function testSerialize()
    {
        $db = $this->getConnection();

        $db->open();
        $serialized = serialize($db);
        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(Connection::class, $unserialized);
        $this->assertEquals(123, $unserialized->createCommand('SELECT 123 FROM DUAL')->queryScalar());
    }

    public function testQuoteTableName()
    {
        $db = $this->getConnection(false);
        $this->assertEquals('"table"', $db->quoteTableName('table'));
        $this->assertEquals('"table"', $db->quoteTableName('"table"'));
        $this->assertEquals('"schema"."table"', $db->quoteTableName('schema.table'));
        $this->assertEquals('"schema"."table"', $db->quoteTableName('schema."table"'));
        $this->assertEquals('"schema"."table"', $db->quoteTableName('"schema"."table"'));
        $this->assertEquals('{{table}}', $db->quoteTableName('{{table}}'));
        $this->assertEquals('(table)', $db->quoteTableName('(table)'));
    }

    public function testQuoteColumnName()
    {
        $db = $this->getConnection(false);
        $this->assertEquals('"column"', $db->quoteColumnName('column'));
        $this->assertEquals('"column"', $db->quoteColumnName('"column"'));
        $this->assertEquals('[[column]]', $db->quoteColumnName('[[column]]'));
        $this->assertEquals('{{column}}', $db->quoteColumnName('{{column}}'));
        $this->assertEquals('(column)', $db->quoteColumnName('(column)'));

        $this->assertEquals('"column"', $db->quoteSql('[[column]]'));
        $this->assertEquals('"column"', $db->quoteSql('{{column}}'));
    }

    public function testQuoteFullColumnName()
    {
        $db = $this->getConnection(false, false);
        $this->assertEquals('"table"."column"', $db->quoteColumnName('table.column'));
        $this->assertEquals('"table"."column"', $db->quoteColumnName('table."column"'));
        $this->assertEquals('"table"."column"', $db->quoteColumnName('"table".column'));
        $this->assertEquals('"table"."column"', $db->quoteColumnName('"table"."column"'));

        $this->assertEquals('[[table.column]]', $db->quoteColumnName('[[table.column]]'));
        $this->assertEquals('{{table}}."column"', $db->quoteColumnName('{{table}}.column'));
        $this->assertEquals('{{table}}."column"', $db->quoteColumnName('{{table}}."column"'));
        $this->assertEquals('{{table}}.[[column]]', $db->quoteColumnName('{{table}}.[[column]]'));
        $this->assertEquals('{{%table}}."column"', $db->quoteColumnName('{{%table}}.column'));
        $this->assertEquals('{{%table}}."column"', $db->quoteColumnName('{{%table}}."column"'));

        $this->assertEquals('"table"."column"', $db->quoteSql('[[table.column]]'));
        $this->assertEquals('"table"."column"', $db->quoteSql('{{table}}.[[column]]'));
        $this->assertEquals('"table"."column"', $db->quoteSql('{{table}}."column"'));
        $this->assertEquals('"table"."column"', $db->quoteSql('{{%table}}.[[column]]'));
        $this->assertEquals('"table"."column"', $db->quoteSql('{{%table}}."column"'));
    }

    public function testTransactionIsolation()
    {
        $db = $this->getConnection(true);

        $transaction = $db->beginTransaction(Transaction::READ_COMMITTED);
        $transaction->commit();

        /* should not be any exception so far */
        $this->assertTrue(true);

        $transaction = $db->beginTransaction(Transaction::SERIALIZABLE);
        $transaction->commit();

        /* should not be any exception so far */
        $this->assertTrue(true);
    }

    /**
     * Note: The READ UNCOMMITTED isolation level allows dirty reads. Oracle Database doesn't use dirty reads, nor does
     * it even allow them.
     *
     * Change Transaction::READ_UNCOMMITTED => Transaction::READ_COMMITTED.
     */
    public function testTransactionShortcutCustom()
    {
        $db = $this->getConnection(true);

        $result = $db->transaction(static function (Connection $db) {
            $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
            return true;
        }, Transaction::READ_COMMITTED);

        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction shortcut'"
        )->queryScalar();
        $this->assertEquals(1, $profilesCount, 'profile should be inserted in transaction shortcut');
    }

    public function testQuoteValue()
    {
        $db = $this->getConnection(false);
        $this->assertEquals(123, $db->quoteValue(123));
        $this->assertEquals("'string'", $db->quoteValue('string'));
        $this->assertEquals("'It''s interesting'", $db->quoteValue("It's interesting"));
    }

    /**
     * Test whether slave connection is recovered when call `getSlavePdo()` after `close()`.
     *
     * {@see https://github.com/yiisoft/yii2/issues/14165}
     */
    public function testGetPdoAfterClose(): void
    {
        $db = $this->getConnection();

        $db->setSlave('1', $this->createConnection(self::DB_DSN));

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
        $cacheKeyNormalizer = new CacheKeyNormalizer();
        $db = $this->getConnection();

        $db->setMaster('1', $this->createConnection(self::DB_DSN));

        $db->setShuffleMasters(false);

        $cacheKey = $cacheKeyNormalizer->normalize(
            ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', $db->getDsn()]
        );

        $this->assertFalse($this->cache->psr()->has($cacheKey));

        $db->open();

        $this->assertFalse(
            $this->cache->psr()->has($cacheKey),
            'Connection was successful – cache must not contain information about this DSN'
        );

        $db->close();

        $db = $this->getConnection();

        $cacheKey = $cacheKeyNormalizer->normalize(
            ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', 'host:invalid']
        );

        $db->setMaster('1', $this->createConnection('host:invalid'));

        $db->setShuffleMasters(true);

        try {
            $db->open();
        } catch (InvalidConfigException $e) {
        }

        $this->assertTrue(
            $this->cache->psr()->has($cacheKey),
            'Connection was not successful – cache must contain information about this DSN'
        );

        $db->close();
    }

    public function testServerStatusCacheCanBeDisabled(): void
    {
        $cacheKeyNormalizer = new CacheKeyNormalizer();

        $db = $this->getConnection();

        $db->setMaster('1', $this->createConnection(self::DB_DSN));

        $this->schemaCache->setEnable(false);

        $db->setShuffleMasters(false);

        $cacheKey = $cacheKeyNormalizer->normalize(
            ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially::', $db->getDsn()]
        );

        $this->assertFalse($this->cache->psr()->has($cacheKey));

        $db->open();

        $this->assertFalse($this->cache->psr()->has($cacheKey), 'Caching is disabled');

        $db->close();

        $cacheKey = $cacheKeyNormalizer->normalize(
            ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', 'host:invalid']
        );

        $db->setMaster('1', $this->createConnection('host:invalid'));

        try {
            $db->open();
        } catch (InvalidConfigException $e) {
        }

        $this->assertFalse($this->cache->psr()->has($cacheKey), 'Caching is disabled');

        $db->close();
    }
}
