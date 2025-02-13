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

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    protected function getConnection(bool $fixture = false): PdoConnectionInterface
    {
        $db = new Connection($this->getDriver(), DbHelper::getSchemaCache());

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . '/Fixture/oci.sql');
        }

        return $db;
    }

    protected static function getDb(): PdoConnectionInterface
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

    private function getDriver(): PdoDriverInterface
    {
        return new Driver($this->getDsn(), self::getUsername(), self::getPassword());
    }

    private static function getSid(): string
    {
        return getenv('YII_ORACLE_SID') ?? '';
    }

    private static function getDatabaseName(): string
    {
        return getenv('YII_ORACLE_DATABASE') ?? '';
    }

    private static function getHost(): string
    {
        return getenv('YII_ORACLE_HOST') ?? '';
    }

    private static function getPort(): string
    {
        return getenv('YII_ORACLE_PORT') ?? '';
    }

    private static function getUsername(): string
    {
        return getenv('YII_ORACLE_USER') ?? '';
    }

    private static function getPassword(): string
    {
        return getenv('YII_ORACLE_PASSWORD') ?? '';
    }
}
