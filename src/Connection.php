<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Throwable;
use Yiisoft\Db\Connection\ServerInfoInterface;
use Yiisoft\Db\Driver\Pdo\AbstractPdoConnection;
use Yiisoft\Db\Driver\Pdo\PdoCommandInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Oracle\Column\ColumnFactory;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Column\ColumnFactoryInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * Implements a connection to a database via PDO (PHP Data Objects) for Oracle Server.
 *
 * @link https://www.php.net/manual/en/ref.pdo-oci.php
 */
final class Connection extends AbstractPdoConnection
{
    public function createCommand(?string $sql = null, array $params = []): PdoCommandInterface
    {
        $command = new Command($this);

        if ($sql !== null) {
            $command->setSql($sql);
        }

        if ($this->logger !== null) {
            $command->setLogger($this->logger);
        }

        if ($this->profiler !== null) {
            $command->setProfiler($this->profiler);
        }

        return $command->bindValues($params);
    }

    public function createTransaction(): TransactionInterface
    {
        return new Transaction($this);
    }

    public function getColumnFactory(): ColumnFactoryInterface
    {
        return $this->columnFactory ??= new ColumnFactory();
    }

    /**
     * Override base behaviour to support Oracle sequences.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidCallException
     * @throws Throwable
     */
    public function getLastInsertID(?string $sequenceName = null): string
    {
        if ($sequenceName === null) {
            throw new InvalidArgumentException('Oracle not support lastInsertId without sequence name.');
        }

        if ($this->isActive()) {
            // get the last insert id from connection
            $sequenceName = $this->getQuoter()->quoteSimpleTableName($sequenceName);

            return (string) $this->createCommand("SELECT $sequenceName.CURRVAL FROM DUAL")->queryScalar();
        }

        throw new InvalidCallException('DB Connection is not active.');
    }

    public function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->queryBuilder ??= new QueryBuilder($this);
    }

    public function getQuoter(): QuoterInterface
    {
        return $this->quoter ??= new Quoter('"', '"', $this->getTablePrefix());
    }

    public function getSchema(): SchemaInterface
    {
        return $this->schema ??= new Schema($this, $this->schemaCache, strtoupper($this->driver->getUsername()));
    }

    public function getServerInfo(): ServerInfoInterface
    {
        return $this->serverInfo ??= new ServerInfo($this);
    }
}
