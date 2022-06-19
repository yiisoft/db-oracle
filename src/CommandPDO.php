<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use PDO;
use PDOException;
use Yiisoft\Db\Driver\PDO\CommandPDO as AbstractCommandPDO;
use Yiisoft\Db\Exception\ConvertException;
use Yiisoft\Db\QueryBuilder\QueryBuilder;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_keys;
use function count;
use function implode;
use function strlen;

/**
 * Command represents an Oracle SQL statement to be executed against a database.
 */
final class CommandPDO extends AbstractCommandPDO
{
    public function queryBuilder(): QueryBuilderInterface
    {
        return $this->db->getQueryBuilder();
    }

    public function schema(): SchemaInterface
    {
        return $this->db->getSchema();
    }

    public function insertEx(string $table, array $columns): bool|array
    {
        $params = [];
        $sql = $this->queryBuilder()->insertEx($table, $columns, $params);

        $tableSchema = $this->db->getSchema()->getTableSchema($table);

        $returnColumns = $tableSchema?->getPrimaryKey() ?? [];
        $columnSchemas = $tableSchema?->getColumns() ?? [];

        $returnParams = [];
        $returning = [];
        foreach ($returnColumns as $name) {
            $phName = QueryBuilder::PARAM_PREFIX . (count($params) + count($returnParams));

            $returnParams[$phName] = [
                'column' => $name,
                'value' => '',
            ];

            if (!isset($columnSchemas[$name]) || $columnSchemas[$name]->getPhpType() !== Schema::PHP_TYPE_INTEGER) {
                $returnParams[$phName]['dataType'] = PDO::PARAM_STR;
            } else {
                $returnParams[$phName]['dataType'] = PDO::PARAM_INT;
            }

            $returnParams[$phName]['size'] = $columnSchemas[$name]->getSize() ?? -1;

            $returning[] = $this->db->getQuoter()->quoteColumnName($name);
        }

        $sql .= ' RETURNING ' . implode(', ', $returning) . ' INTO ' . implode(', ', array_keys($returnParams));

        $this->setSql($sql)->bindValues($params);
        $this->prepare(false);

        /** @psalm-var array<string, array{column: string, value: mixed, dataType: int, size: int}> $returnParams */
        foreach ($returnParams as $name => &$value) {
            $this->bindParam($name, $value['value'], $value['dataType'], $value['size']);
        }

        if (!$this->execute()) {
            return false;
        }

        $result = [];

        foreach ($returnParams as $value) {
            /** @var mixed */
            $result[$value['column']] = $value['value'];
        }

        return $result;
    }

    protected function bindPendingParams(): void
    {
        $paramsPassedByReference = [];

        $params = $this->params;

        foreach ($params as $name => $value) {
            if (PDO::PARAM_STR === $value->getType()) {
                /** @var mixed */
                $paramsPassedByReference[$name] = $value->getValue();
                $this->pdoStatement?->bindParam(
                    $name,
                    $paramsPassedByReference[$name],
                    $value->getType(),
                    strlen((string) $value->getValue())
                );
            } else {
                $this->pdoStatement?->bindValue($name, $value->getValue(), $value->getType());
            }
        }
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
                    $this->db->transaction(fn (string $rawSql) => $this->internalExecute($rawSql), $this->isolationLevel);
                } else {
                    $this->pdoStatement?->execute();
                }
                break;
            } catch (PDOException $e) {
                $rawSql = $rawSql ?: $this->getRawSql();
                $e = (new ConvertException($e, $rawSql))->run();

                if ($this->retryHandler === null || !($this->retryHandler)($e, $attempt)) {
                    throw $e;
                }
            }
        }
    }
}
