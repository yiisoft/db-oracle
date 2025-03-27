<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Support;

use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Driver\Pdo\PdoDriverInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Oracle\Connection;
use Yiisoft\Db\Oracle\Dsn;
use Yiisoft\Db\Oracle\Driver;
use Yiisoft\Db\Tests\Support\DbHelper;

trait TestTrait
{
    private string $dsn = '';

    private string $fixture = 'oci.sql';

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
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
        $dsn = (new Dsn(
            host: self::getHost(),
            databaseName: self::getSid(),
            port: self::getPort(),
            options: ['charset' => 'AL32UTF8'],
        ))->asString();

        return new Connection(new Driver($dsn, self::getUsername(), self::getPassword()), DbHelper::getSchemaCache());
    }

    protected function getDsn(): string
    {
        if ($this->dsn === '') {
            $this->dsn = (new Dsn(
                host: self::getHost(),
                databaseName: self::getSid(),
                port: self::getPort(),
                options: ['charset' => 'AL32UTF8'],
            ))->asString();
        }

        return $this->dsn;
    }

    protected function getDriverName(): string
    {
        return 'oci';
    }

    protected function setDsn(string $dsn): void
    {
        $this->dsn = $dsn;
    }

    private function getDriver(): Driver
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
