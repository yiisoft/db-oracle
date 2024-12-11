<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Schema\Builder\AbstractColumn;

/**
 * It's a utility that provides a convenient way to create column schema for use with {@see Schema} for Oracle Server.
 *
 * It provides methods for specifying the properties of a column, such as its type, size, default value, and whether it
 * is nullable or not. It also provides a method for creating a column schema based on the specified properties.
 *
 * For example, the following code creates a column schema for an integer column:
 *
 * ```php
 * $column = (new Column(ColumnType::INTEGER))->notNull()->defaultValue(0);
 * ```
 *
 * Provides a fluent interface, which means that the methods can be chained together to create a column schema with
 * many properties in a single line of code.
 *
 * @psalm-suppress DeprecatedClass
 *
 * @deprecated Use {@see StringColumnSchema} or other column classes instead. Will be removed in 2.0.0.
 */
final class Column extends AbstractColumn
{
    /**
     * Builds the unsigned string for column. Defaults to unsupported.
     *
     * @return string A string containing UNSIGNED keyword.
     */
    protected function buildUnsignedString(): string
    {
        return $this->isUnsigned() ? ' UNSIGNED' : '';
    }

    /**
     * Builds the full string for the column's schema.
     */
    public function asString(): string
    {
        $format = match ($this->getTypeCategory()) {
            self::TYPE_CATEGORY_PK => '{type}{length}{check}{append}',
            self::TYPE_CATEGORY_NUMERIC => '{type}{length}{unsigned}{default}{notnull}{check}{append}',
            self::TYPE_CATEGORY_UUID => '{type}{notnull}{unique}{default}{check}{comment}{append}',
            self::TYPE_CATEGORY_UUID_PK => '{type}{notnull}{check}{comment}{append}',
            default => '{type}{length}{default}{notnull}{check}{append}',
        };

        return $this->buildCompleteString($format);
    }
}
