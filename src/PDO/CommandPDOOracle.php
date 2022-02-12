<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\PDO;

use PDO;
use PDOException;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Connection\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Query\QueryBuilderInterface;

/**
 * Command represents an Oracle SQL statement to be executed against a database.
 */
final class CommandPDOOracle extends Command
{
    private int $i = 0;

    public function __construct(private ConnectionPDOInterface $db, QueryCache $queryCache)
    {
        parent::__construct($queryCache);
    }

    public function queryBuilder(): QueryBuilderInterface
    {
        return $this->db->getQueryBuilder();
    }

    public function prepare(?bool $forRead = null): void
    {
        if (isset($this->pdoStatement)) {
            $this->bindPendingParams();

            return;
        }

        $sql = $this->getSql();

        if ($this->db->getTransaction()) {
            /** master is in a transaction. use the same connection. */
            $forRead = false;
        }

        if ($forRead || ($forRead === null && $this->db->getSchema()->isReadQuery($sql))) {
            $pdo = $this->db->getSlavePdo();
        } else {
            $pdo = $this->db->getMasterPdo();
        }

        try {
            $this->pdoStatement = $pdo->prepare($sql);
            $this->bindPendingParams();
        } catch (PDOException $e) {
            $message = $e->getMessage() . "\nFailed to prepare SQL: $sql";
            $errorInfo = $e->errorInfo ?? null;

            throw new Exception($message, $errorInfo, $e);
        }
    }

    protected function bindPendingParams(): void
    {
        $paramsPassedByReference = [];

        foreach ($this->pendingParams as $name => $value) {
            if (PDO::PARAM_STR === $value[1]) {
                $paramsPassedByReference[$name] = $value[0];
                $this->pdoStatement?->bindParam(
                    $name,
                    $paramsPassedByReference[$name],
                    $value[1],
                    strlen($value[0])
                );
            } else {
                $this->pdoStatement?->bindValue($name, $value[0], $value[1]);
            }
        }

        $this->pendingParams = [];
    }

    protected function getCacheKey(string $method, ?int $fetchMode, string $rawSql): array
    {
        return [
            __CLASS__,
            $method,
            $fetchMode,
            $this->db->getDriver()->getDsn(),
            $this->db->getDriver()->getUsername(),
            $rawSql,
        ];
    }

    protected function internalExecute(?string $rawSql): void
    {
        $attempt = 0;

        while (true) {
            try {
                if (
                    ++$attempt === 1
                    && $this->isolationLevel !== null
                    && $this->db->getTransaction() === null
                ) {
                    $this->db->transaction(fn ($rawSql) => $this->internalExecute($rawSql), $this->isolationLevel);
                } else {
                    $this->pdoStatement->execute();
                }
                break;
            } catch (PDOException $e) {
                $rawSql = $rawSql ?: $this->getRawSql();
                $e = $this->db->getSchema()->convertException($e, $rawSql);

                if ($this->retryHandler === null || !($this->retryHandler)($e, $attempt)) {
                    throw $e;
                }
            }
        }
    }
}
