<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Support;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Oracle\ConnectionPDO;
use Yiisoft\Db\Oracle\Dsn;
use Yiisoft\Db\Oracle\PDODriver;
use Yiisoft\Db\Tests\Support\DbHelper;

trait TestTrait
{
    private string $dsn = 'oci:dbname=localhost/XE;';

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    protected function getConnection(bool $fixture = false): ConnectionPDOInterface
    {
        $db = new ConnectionPDO(
            new PDODriver($this->getDsn(), 'system', 'root'),
            DbHelper::getSchemaCache(),
        );

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . '/Fixture/oci.sql');
        }

        return $db;
    }

    protected static function getDb(): ConnectionPDOInterface
    {
        $dsn = (new Dsn('oci', 'localhost', 'XE', '1521', ['charset' => 'AL32UTF8']))->asString();

        return new ConnectionPDO(
            new PDODriver($dsn, 'system', 'root'),
            DbHelper::getSchemaCache(),
        );
    }

    protected function getDsn(): string
    {
        if ($this->dsn === '') {
            $this->dsn = (new Dsn('oci', 'localhost', 'XE', '1521', ['charset' => 'AL32UTF8']))->asString();
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
}
