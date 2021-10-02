<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PHPUnit\Framework\TestCase as AbstractTestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\TestUtility\TestTrait;

class TestCase extends AbstractTestCase
{
    use TestTrait;

    protected const DB_CONNECTION_CLASS = \Yiisoft\Db\Oracle\Connection::class;
    protected const DB_DRIVERNAME = 'oci';
    protected const DB_DSN = 'oci:dbname=localhost/XE;';
    protected const DB_FIXTURES_PATH = __DIR__ . '/Fixture/oci.sql';
    protected const DB_USERNAME = 'system';
    protected const DB_PASSWORD = 'oracle';
    protected const DB_CHARSET = 'AL32UTF8';
    protected array $dataProvider;
    protected string $likeEscapeCharSql = '';
    protected array $likeParameterReplacements = [];
    protected ConnectionInterface $connection;

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
}
