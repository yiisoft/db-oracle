<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PDO;
use Yiisoft\Cache\CacheKeyNormalizer;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Oracle\PDO\TransactionPDOOracle;
use Yiisoft\Db\TestSupport\TestConnectionTrait;

/**
 * @group oracle
 */
final class ConnectionTest extends TestCase
{
    use TestConnectionTrait;

    public function testConstruct(): void
    {
        $db = $this->getConnection();
        $this->assertEquals($this->dsn, $db->getDriver()->getDsn());
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

        $db = $this->getConnection(false, 'unknown::memory:');
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

        $transaction = $db->beginTransaction(TransactionPDOOracle::READ_COMMITTED);
        $transaction->commit();
        /* should not be any exception so far */
        $this->assertTrue(true);

        $transaction = $db->beginTransaction(TransactionPDOOracle::SERIALIZABLE);
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
        }, TransactionPDOOracle::READ_COMMITTED);
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
        } catch (InvalidConfigException $e) {
        }

        $this->assertFalse($this->cache->psr()->has($cacheKey), 'Caching is disabled');

        $db->close();
    }
}
