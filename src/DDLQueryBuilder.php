<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\DDLQueryBuilder as AbstractDDLQueryBuilder;
use Yiisoft\Db\Query\QueryBuilderInterface;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

final class DDLQueryBuilder extends AbstractDDLQueryBuilder
{
    public function __construct(
        private QueryBuilderInterface $queryBuilder,
        private QuoterInterface $quoter,
        SchemaInterface $schema
    ) {
        parent::__construct($queryBuilder, $quoter, $schema);
    }

    public function addForeignKey(string $name, string $table, array|string $columns, string $refTable, array|string $refColumns, ?string $delete = null, ?string $update = null): string
    {
        $sql = 'ALTER TABLE ' . $this->quoter->quoteTableName($table)
            . ' ADD CONSTRAINT ' . $this->quoter->quoteColumnName($name)
            . ' FOREIGN KEY (' . $this->queryBuilder->buildColumns($columns) . ')'
            . ' REFERENCES ' . $this->quoter->quoteTableName($refTable)
            . ' (' . $this->queryBuilder->buildColumns($refColumns) . ')';

        if ($delete !== null) {
            $sql .= ' ON DELETE ' . $delete;
        }

        if ($update !== null) {
            throw new Exception('Oracle does not support ON UPDATE clause.');
        }

        return $sql;
    }

    public function alterColumn(string $table, string $column, ColumnSchemaBuilder|string $type): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' MODIFY '
            . $this->quoter->quoteColumnName($column)
            . ' ' . $this->queryBuilder->getColumnType($type);
    }

    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        throw new NotSupportedException('Oracle does not support enabling/disabling integrity check.');
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

    public function dropIndex(string $name, string $table): string
    {
        return 'DROP INDEX ' . $this->quoter->quoteTableName($name);
    }

    public function renameTable(string $oldName, string $newName): string
    {
        return 'ALTER TABLE ' . $this->quoter->quoteTableName($oldName) . ' RENAME TO ' .
            $this->quoter->quoteTableName($newName);
    }
}
