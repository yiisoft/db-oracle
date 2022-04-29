<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\PDO;

use PDO;
use Yiisoft\Db\Command\CommandPDOInterface;
use Yiisoft\Db\Connection\ConnectionPDO;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Oracle\Quoter;
use Yiisoft\Db\Oracle\Schema;
use Yiisoft\Db\Query\QueryBuilderInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

use function constant;

/**
 * Database connection class prefilled for Oracle SQL Server.
 * The class Connection represents a connection to a database via [PDO](https://secure.php.net/manual/en/book.pdo.php).
 */
final class ConnectionPDOOracle extends ConnectionPDO
{
    public function createCommand(?string $sql = null, array $params = []): CommandPDOInterface
    {
        $command = new CommandPDOOracle($this, $this->queryCache);

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
        return new TransactionPDOOracle($this);
    }

    public function getDriverName(): string
    {
        return 'oci';
    }

    /**
     * Override base behaviour
     */
    public function getLastInsertID(string $sequenceName = null): string
    {
        if ($sequenceName === null) {
            throw new InvalidArgumentException('Oracle not support lastInsertId without sequence name');
        }

        if ($this->isActive()) {
            /* get the last insert id from connection */
            $sequenceName = $this->getQuoter()->quoteSimpleTableName($sequenceName);

            return (string) $this->createCommand("SELECT $sequenceName.CURRVAL FROM DUAL")->queryScalar();
        }

        throw new InvalidCallException('DB Connection is not active.');
    }

    /**
     * @throws Exception|InvalidConfigException
     */
    public function getQueryBuilder(): QueryBuilderInterface
    {
        if ($this->queryBuilder === null) {
            $this->queryBuilder = new QueryBuilderPDOOracle(
                $this->createCommand(),
                $this->getQuoter(),
                $this->getSchema(),
            );
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

    /**
     * Initializes the DB connection.
     *
     * This method is invoked right after the DB connection is established.
     *
     * The default implementation turns on `PDO::ATTR_EMULATE_PREPARES`.
     *
     * if {@see emulatePrepare} is true, and sets the database {@see charset} if it is not empty.
     *
     * It then triggers an {@see EVENT_AFTER_OPEN} event.
     */
    protected function initConnection(): void
    {
        $this->pdo = $this->driver->createConnection();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($this->getEmulatePrepare() !== null && constant('PDO::ATTR_EMULATE_PREPARES')) {
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->getEmulatePrepare());
        }
    }
}
