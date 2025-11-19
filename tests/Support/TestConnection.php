<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Support;

use Yiisoft\Db\Oracle\Connection;
use Yiisoft\Db\Oracle\Driver;
use Yiisoft\Db\Oracle\Dsn;
use Yiisoft\Db\Tests\Support\TestHelper;

final class TestConnection
{
    private static ?string $dsn = null;
    private static ?Connection $connection = null;

    public static function getShared(): Connection
    {
        $db = self::$connection ??= self::create();
        $db->getSchema()->refresh();
        return $db;
    }

    public static function dsn(): string
    {
        return self::$dsn ??= (string) new Dsn(
            host: self::host(),
            databaseName: self::sid(),
            port: self::port(),
            options: ['charset' => 'AL32UTF8'],
        );
    }

    public static function create(?string $dsn = null): Connection
    {
        return new Connection(self::createDriver($dsn), TestHelper::createMemorySchemaCache());
    }

    public static function createDriver(?string $dsn = null): Driver
    {
        return new Driver($dsn ?? self::dsn(), self::username(), self::password());
    }

    public static function databaseName(): string
    {
        return getenv('YII_ORACLE_DATABASE') ?: 'YIITEST';
    }

    private static function sid(): string
    {
        return getenv('YII_ORACLE_SID') ?: 'XE';
    }

    private static function host(): string
    {
        return getenv('YII_ORACLE_HOST') ?: 'localhost';
    }

    private static function port(): string
    {
        return getenv('YII_ORACLE_PORT') ?: '1521';
    }

    private static function username(): string
    {
        return getenv('YII_ORACLE_USER') ?: 'system';
    }

    private static function password(): string
    {
        return getenv('YII_ORACLE_PASSWORD') ?: 'root';
    }
}
