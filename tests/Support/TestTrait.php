<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Support;

use Yiisoft\Db\Oracle\Connection;
use Yiisoft\Db\Oracle\Dsn;
use Yiisoft\Db\Oracle\Driver;
use Yiisoft\Db\Tests\Support\DbHelper;

trait TestTrait
{
    private string $dsn = '';

    private string $fixture = 'oci.sql';

    public static function setUpBeforeClass(): void
    {
        $db = self::getDb();

        DbHelper::loadFixture($db, __DIR__ . '/Fixture/oci.sql');

        $db->close();
    }

    protected function getConnection(bool $fixture = false): Connection
    {
        $db = new Connection($this->getDriver(), DbHelper::getSchemaCache());

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . "/Fixture/$this->fixture");
        }

        return $db;
    }

    protected static function getDb(): Connection
    {
        $dsn = (string) new Dsn(
            host: self::getHost(),
            databaseName: self::getSid(),
            port: self::getPort(),
            options: ['charset' => 'AL32UTF8'],
        );

        return new Connection(new Driver($dsn, self::getUsername(), self::getPassword()), DbHelper::getSchemaCache());
    }

    protected function getDsn(): string
    {
        if ($this->dsn === '') {
            $this->dsn = (string) new Dsn(
                host: self::getHost(),
                databaseName: self::getSid(),
                port: self::getPort(),
                options: ['charset' => 'AL32UTF8'],
            );
        }

        return $this->dsn;
    }

    protected static function getDriverName(): string
    {
        return 'oci';
    }

    protected function setDsn(string $dsn): void
    {
        $this->dsn = $dsn;
    }

    protected function getDriver(): Driver
    {
        return new Driver($this->getDsn(), self::getUsername(), self::getPassword());
    }

    private static function getSid(): string
    {
        return getenv('YII_ORACLE_SID') ?: 'XE';
    }

    private static function getDatabaseName(): string
    {
        return getenv('YII_ORACLE_DATABASE') ?: 'YIITEST';
    }

    private static function getHost(): string
    {
        return getenv('YII_ORACLE_HOST') ?: 'localhost';
    }

    private static function getPort(): string
    {
        return getenv('YII_ORACLE_PORT') ?: '1521';
    }

    private static function getUsername(): string
    {
        return getenv('YII_ORACLE_USER') ?: 'system';
    }

    private static function getPassword(): string
    {
        return getenv('YII_ORACLE_PASSWORD') ?: 'root';
    }
}
