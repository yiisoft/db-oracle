<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Schema\AbstractTableSchema;

/**
 * Implements the Oracle Server specific table schema.
 */
final class TableSchema extends AbstractTableSchema
{
    public function foreignKey(string|int $id, array $to): void
    {
        $this->foreignKeys[] = $to;
    }
}
