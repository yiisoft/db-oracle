<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Schema\AbstractColumnSchemaBuilder;

/**
 * ColumnSchemaBuilder is the schema builder for Oracle databases.
 */
final class ColumnSchemaBuilder extends AbstractColumnSchemaBuilder implements \Stringable
{
    /**
     * Builds the unsigned string for column. Defaults to unsupported.
     *
     * @return string a string containing UNSIGNED keyword.
     */
    protected function buildUnsignedString(): string
    {
        return $this->isUnsigned() ? ' UNSIGNED' : '';
    }

    /**
     * Builds the full string for the column's schema.
     *
     * @return string
     */
    public function __toString(): string
    {
        $format = match ($this->getTypeCategory()) {
            self::CATEGORY_PK => '{type}{length}{check}{append}',
            self::CATEGORY_NUMERIC => '{type}{length}{unsigned}{default}{notnull}{check}{append}',
            default => '{type}{length}{default}{notnull}{check}{append}',
        };

        return $this->buildCompleteString($format);
    }
}
