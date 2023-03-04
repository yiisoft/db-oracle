<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\AbstractColumnSchema;
use Yiisoft\Db\Schema\SchemaInterface;

use function is_string;
use function preg_replace;
use function uniqid;

/**
 * Represents the metadata of a column in a database table for Oracle Server. It provides information about the column's
 * name, type, size, precision, and other details.
 *
 * Is used to store and retrieve metadata about a column in a database table. It is typically used in conjunction with
 * the TableSchema class, which represents the metadata of a database table as a whole.
 *
 * Here is an example of how the ColumnSchema class might be used:
 *
 * ```php
 * use Yiisoft\Db\Mysql\ColumnSchema;
 *
 * $column = new ColumnSchema();
 * $column->name('id');
 * $column->allowNull(false);
 * $column->dbType('number');
 * $column->phpType('integer');
 * $column->type('integer');
 * $column->defaultValue(0);
 * $column->autoIncrement(true);
 * $column->primaryKey(true);
 * ```
 */
final class ColumnSchema extends AbstractColumnSchema
{
    public function dbTypecast(mixed $value): mixed
    {
        if ($this->getType() === SchemaInterface::TYPE_BINARY && $this->getDbType() === 'BLOB') {
            if ($value instanceof ParamInterface && is_string($value->getValue())) {
                $value = (string) $value->getValue();
            }

            if (is_string($value)) {
                $placeholder = uniqid('exp_' . preg_replace('/[^a-z0-9]/i', '', $this->getName()), true);
                return new Expression('TO_BLOB(UTL_RAW.CAST_TO_RAW(:' . $placeholder . '))', [$placeholder => $value]);
            }
        }

        return parent::dbTypecast($value);
    }
}
