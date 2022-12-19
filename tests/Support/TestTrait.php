<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Support;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Oracle\ConnectionPDO;
use Yiisoft\Db\Oracle\PDODriver;
use Yiisoft\Db\Tests\Support\DbHelper;

use function str_replace;

trait TestTrait
{
    private string $dsn = 'oci:dbname=localhost/XE;';

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    protected function getConnection(bool $fixture = false): ConnectionPDOInterface
    {
        $pdoDriver = new PDODriver($this->dsn, 'system', 'oracle');
        $pdoDriver->setCharset('AL32UTF8');

        $db = new ConnectionPDO($pdoDriver, DbHelper::getQueryCache(), DbHelper::getSchemaCache());

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . '/Fixture/oci.sql');
        }

        return $db;
    }

    protected function getDriverName(): string
    {
        return 'oci';
    }

    protected function setDsn(string $dsn): void
    {
        $this->dsn = $dsn;
    }

    private function changeSqlForOracleBatchInsert(string &$str): void
    {
        $str = str_replace('INSERT INTO', 'INSERT ALL  INTO', $str) . ' SELECT 1 FROM SYS.DUAL';
    }
}
