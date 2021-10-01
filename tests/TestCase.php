<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PHPUnit\Framework\TestCase as AbstractTestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Oracle\Connection;
use Yiisoft\Db\TestUtility\TestTrait;

class TestCase extends AbstractTestCase
{
    use TestTrait;

    protected const DB_DSN = 'oci:dbname=localhost/XE;charset=AL32UTF8;';
    protected const DB_FIXTURES_PATH = __DIR__ . '/Fixture/oci.sql';
    protected array $dataProvider;
    protected string $likeEscapeCharSql = '';
    protected array $likeParameterReplacements = [];
    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->createConnection(self::DB_DSN);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->connection->close();
        unset(
            $this->cache,
            $this->connection,
            $this->logger,
            $this->queryCache,
            $this->schemaCache,
            $this->profiler
        );
    }

    protected function createConnection(string $dsn = null): ?ConnectionInterface
    {
        $db = null;

        if ($dsn !== null) {
            $db = new Connection($dsn, $this->createQueryCache(), $this->createSchemaCache());
            $db->setLogger($this->createLogger());
            $db->setProfiler($this->createProfiler());
            $db->setUsername('system');
            $db->setPassword('oracle');
        }

        return $db;
    }

    protected function prepareDatabase(string $dsn = null, $fixture = null): void
    {
        $fixture = $fixture ?? self::DB_FIXTURES_PATH;

        if ($dsn !== null) {
            $this->connection = $this->createConnection($dsn);
        }

        $this->connection->open();

        if ($fixture !== null) {
            [$drops, $creates] = explode('/* STATEMENTS */', file_get_contents($fixture), 2);

            [$statements, $triggers, $data] = explode('/* TRIGGERS */', $creates, 3);

            $lines = array_merge(
                explode('--', $drops),
                explode(';', $statements),
                explode('/', $triggers),
                explode(';', $data),
            );

            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    $this->connection->getPDO()->exec($line);
                }
            }
        }
    }

    /**
     * Adjust dbms specific escaping.
     *
     * @param string $sql
     *
     * @return string
     */
    protected function replaceQuotes(string $sql): string
    {
        return str_replace(['[[', ']]'], '"', $sql);
    }
}
