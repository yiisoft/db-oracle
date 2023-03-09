<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Throwable;
use Yiisoft\Db\Driver\PDO\AbstractConnectionPDO;
use Yiisoft\Db\Driver\PDO\CommandPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * Implements a connection to a database via PDO (PHP Data Objects) for MySQL, MariaDb Server.
 *
 * @link https://www.php.net/manual/en/ref.pdo-oci.php
 */
final class ConnectionPDO extends AbstractConnectionPDO
{
    public function createCommand(string $sql = null, array $params = []): CommandPDOInterface
    {
        $command = new CommandPDO($this);

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
        return new TransactionPDO($this);
    }

    /**
     * Override base behaviour to support Oracle sequences.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidCallException
     * @throws Throwable
     */
    public function getLastInsertID(string $sequenceName = null): string
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
        if ($this->queryBuilder === null) {
            $this->queryBuilder = new QueryBuilder($this->getQuoter(), $this->getSchema());
        }

        return $this->queryBuilder;
    }

    public function getQuoter(): QuoterInterface
    {
        if ($this->quoter === null) {
            $this->quoter = new Quoter('"', '"', $this->getTablePrefix());
        }

        return $this->quoter;
    }

    public function getSchema(): SchemaInterface
    {
        if ($this->schema === null) {
            $this->schema = new Schema($this, $this->schemaCache, strtoupper($this->driver->getUsername()));
        }

        return $this->schema;
    }
}
