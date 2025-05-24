<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use PDO;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Constant\PhpType;
use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\AbstractQueryBuilder;

use function array_keys;
use function array_map;
use function count;
use function implode;
use function strlen;

/**
 * Implements a database command that can be executed against a PDO (PHP Data Object) database connection for Oracle
 * Server.
 */
final class Command extends AbstractPdoCommand
{
    public function insertWithReturningPks(string $table, array|QueryInterface $columns): array|false
    {
        $tableSchema = $this->db->getSchema()->getTableSchema($table);
        $returnColumns = $tableSchema?->getPrimaryKey() ?? [];

        if ($returnColumns === []) {
            if ($this->insert($table, $columns)->execute() === 0) {
                return false;
            }

            return [];
        }

        if ($columns instanceof QueryInterface) {
            throw new NotSupportedException(
                __METHOD__ . '() is not supported by Oracle when inserting sub-query.'
            );
        }

        $params = [];
        $sql = $this->getQueryBuilder()->insert($table, $columns, $params);

        /** @var TableSchema $tableSchema */
        $tableColumns = $tableSchema->getColumns();
        $returnParams = [];

        foreach ($returnColumns as $name) {
            $phName = AbstractQueryBuilder::PARAM_PREFIX . (count($params) + count($returnParams));

            $returnParams[$phName] = [
                'column' => $name,
                'value' => '',
            ];

            $column = $tableColumns[$name];

            if ($column->getPhpType() !== PhpType::INT) {
                $returnParams[$phName]['dataType'] = PDO::PARAM_STR;
            } else {
                $returnParams[$phName]['dataType'] = PDO::PARAM_INT;
            }

            $returnParams[$phName]['size'] = ($column->getSize() ?? 3998) + 2;
        }

        $quotedReturnColumns = array_map($this->db->getQuoter()->quoteColumnName(...), $returnColumns);

        $sql .= ' RETURNING ' . implode(', ', $quotedReturnColumns) . ' INTO ' . implode(', ', array_keys($returnParams));

        $this->setSql($sql)->bindValues($params);
        $this->prepare(false);

        /** @psalm-var array<string, array{column: string, value: mixed, dataType: DataType::*, size: int}> $returnParams */
        foreach ($returnParams as $name => &$value) {
            $this->bindParam($name, $value['value'], $value['dataType'], $value['size']);
        }

        unset($value);

        if ($this->execute() === 0) {
            return false;
        }

        $result = [];

        foreach ($returnParams as $value) {
            $result[$value['column']] = $value['value'];
        }

        if ($this->phpTypecasting) {
            foreach ($result as $column => &$value) {
                $value = $tableColumns[$column]->phpTypecast($value);
            }
        }

        return $result;
    }

    public function showDatabases(): array
    {
        $sql = <<<SQL
        SELECT PDB_NAME FROM DBA_PDBS WHERE PDB_NAME NOT IN ('PDB\$SEED', 'PDB\$ROOT', 'ORCLPDB1', 'XEPDB1')
        SQL;

        return $this->setSql($sql)->queryColumn();
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
}
