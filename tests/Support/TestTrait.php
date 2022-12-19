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
    private ConnectionPDOInterface|null $db = null;
    private string $dsn = 'oci:dbname=localhost/XE;';

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    protected function getConnection(bool $fixture = false): ConnectionPDOInterface
    {
        $pdoDriver = new PDODriver($this->dsn, 'system', 'oracle');
        $pdoDriver->setCharset('AL32UTF8');

        $this->db = new ConnectionPDO($pdoDriver, DbHelper::getQueryCache(), DbHelper::getSchemaCache());

        if ($fixture) {
            DbHelper::loadFixture($this->db, __DIR__ . '/Fixture/oci.sql');
        }

        return $this->db;
    }

    protected function getDriverName(): string
    {
        return 'oci';
    }

    protected function setDsn(string $dsn): void
    {
        $this->dsn = $dsn;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->db?->close();
    }

    private function changeSqlForOracleBatchInsert(string &$str): void
    {
        $str = str_replace('INSERT INTO', 'INSERT ALL  INTO', $str) . ' SELECT 1 FROM SYS.DUAL';
    }
}
