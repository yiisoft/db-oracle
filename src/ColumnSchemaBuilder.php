<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Schema\AbstractColumnSchemaBuilder;

/**
 * It's a utility that provides a convenient way to create column schema for use with {@see Schema} for Oracle Server.
 *
 * It provides methods for specifying the properties of a column, such as its type, size, default value, and whether it
 * is nullable or not. It also provides a method for creating a column schema based on the specified properties.
 *
 * For example, the following code creates a column schema for an integer column:
 *
 * ```php
 * $column = (new ColumnSchemaBuilder(SchemaInterface::TYPE_INTEGER))->notNull()->defaultValue(0);
 * ```
 *
 * Provides a fluent interface, which means that the methods can be chained together to create a column schema with
 * many properties in a single line of code.
 */
final class ColumnSchemaBuilder extends AbstractColumnSchemaBuilder
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
            self::CATEGORY_PK => '{type}{length}{check}{append}',
            self::CATEGORY_NUMERIC => '{type}{length}{unsigned}{default}{notnull}{check}{append}',
            default => '{type}{length}{default}{notnull}{check}{append}',
        };

        return $this->buildCompleteString($format);
    }
}
