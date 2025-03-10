<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder;
use Yiisoft\Db\Schema\Column\ColumnInterface;

/**
 * Implements a (Data Definition Language) SQL statements for Oracle Server.
 */
final class DDLQueryBuilder extends AbstractDDLQueryBuilder
{
    public function addDefaultValue(string $table, string $name, string $column, mixed $value): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by Oracle.');
    }

    public function addForeignKey(
        string $table,
        string $name,
        array|string $columns,
        string $referenceTable,
        array|string $referenceColumns,
        ?string $delete = null,
        ?string $update = null
    ): string {
        $sql = 'ALTER TABLE ' . $this->quoter->quoteTableName($table)
            . ' ADD CONSTRAINT ' . $this->quoter->quoteColumnName($name)
            . ' FOREIGN KEY (' . $this->queryBuilder->buildColumns($columns) . ')'
            . ' REFERENCES ' . $this->quoter->quoteTableName($referenceTable)
            . ' (' . $this->queryBuilder->buildColumns($referenceColumns) . ')';

        if ($delete !== null) {
            $sql .= ' ON DELETE ' . $delete;
        }

        if ($update !== null) {
            throw new Exception('Oracle does not support ON UPDATE clause.');
        }

        return $sql;
    }

    public function alterColumn(string $table, string $column, ColumnInterface|string $type): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' MODIFY '
            . $this->quoter->quoteColumnName($column)
            . ' ' . $this->queryBuilder->buildColumnDefinition($type);
    }

    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by Oracle.');
    }

    public function dropCommentFromColumn(string $table, string $column): string
    {
        return 'COMMENT ON COLUMN '
            . $this->quoter->quoteTableName($table)
            . '.'
            . $this->quoter->quoteColumnName($column)
            . " IS ''";
    }

    public function dropCommentFromTable(string $table): string
    {
        return 'COMMENT ON TABLE ' . $this->quoter->quoteTableName($table) . " IS ''";
    }

    public function dropDefaultValue(string $table, string $name): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by Oracle.');
    }

    public function dropIndex(string $table, string $name): string
    {
        return 'DROP INDEX ' . $this->quoter->quoteTableName($name);
    }

    public function dropTable(string $table, bool $ifExists = false, bool $cascade = false): string
    {
        return 'DROP TABLE '
            . ($ifExists ? 'IF EXISTS ' : '')
            . $this->quoter->quoteTableName($table)
            . ($cascade ? ' CASCADE CONSTRAINTS' : '');
    }

    public function renameTable(string $oldName, string $newName): string
    {
        return 'ALTER TABLE ' . $this->quoter->quoteTableName($oldName) . ' RENAME TO ' .
            $this->quoter->quoteTableName($newName);
    }
}
