<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Schema\TableSchema as AbstractTableSchema;

/**
 * TableSchema represents the metadata of a database table.
 */
final class TableSchema extends AbstractTableSchema
{
    public function foreignKey(array $value): void
    {
        $this->foreignKeys[] = $value;
    }
}
