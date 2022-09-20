<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Exception;
use PHPUnit\Framework\TestCase as AbstractTestCase;
use Yiisoft\Db\Oracle\PDODriver;
use Yiisoft\Db\Oracle\ConnectionPDO;
use Yiisoft\Db\TestSupport\TestTrait;

class TestCase extends AbstractTestCase
{
    use TestTrait;

    protected string $drivername = 'oci';
    protected string $dsn = 'oci:dbname=localhost/XE;';
    protected string $username = 'system';
    protected string $password = 'oracle';
    protected string $charset = 'AL32UTF8';
    protected array $dataProvider;
    protected string $likeEscapeCharSql = '';
    protected array $likeParameterReplacements = [];
    protected ?ConnectionPDO $db = null;

    /**
     * @param bool $reset whether to clean up the test database.
     *
     * @return ConnectionPDO
     */
    protected function getConnection(
        $reset = false,
        ?string $dsn = null,
        string $fixture = __DIR__ . '/Fixture/oci.sql'
    ): ConnectionPDO {
        $pdoDriver = new PDODriver($dsn ?? $this->dsn, $this->username, $this->password);
        $this->db = new ConnectionPDO($pdoDriver, $this->createQueryCache(), $this->createSchemaCache());
        $this->db->setLogger($this->createLogger());
        $this->db->setProfiler($this->createProfiler());

        if ($reset === false) {
            return $this->db;
        }

        try {
            $this->prepareDatabase($this->db, $fixture);
        } catch (Exception $e) {
            $this->markTestSkipped('Something wrong when preparing database: ' . $e->getMessage());
        }

        return $this->db;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->db?->close();
        unset(
            $this->cache,
            $this->db,
            $this->logger,
            $this->queryCache,
            $this->schemaCache,
            $this->profiler
        );
    }

    protected function changeSqlForOracleBatchInsert(string &$str) {
        $str = str_replace('INSERT INTO', 'INSERT ALL  INTO', $str) .' SELECT 1 FROM SYS.DUAL';
    }
}
