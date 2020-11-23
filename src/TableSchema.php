<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Schema\TableSchema as AbstractTableSchema;

/**
 * TableSchema represents the metadata of a database table.
 */
final class TableSchema extends AbstractTableSchema
{
    private array $foreignKeys = [];

    /**
     * @return array foreign keys of this table. Each array element is of the following structure:
     *
     * ```php
     * [
     *  'ForeignTableName',
     *  'fk1' => 'pk1',  // pk1 is in foreign table
     *  'fk2' => 'pk2',  // if composite foreign key
     * ]
     * ```
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function foreignKey(string $key, array $value): void
    {
        $this->foreignKeys[] = $value;
    }

    public function foreignKeys(array $value): void
    {
        $this->foreignKeys = $value;
    }
}
