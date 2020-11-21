<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Schema\ColumnSchemaBuilder as AbstractColumnSchemaBuilder;

/**
 * ColumnSchemaBuilder is the schema builder for Oracle databases.
 */
final class ColumnSchemaBuilder extends AbstractColumnSchemaBuilder
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
        switch ($this->getTypeCategory()) {
            case self::CATEGORY_PK:
                $format = '{type}{length}{check}{append}';
                break;
            case self::CATEGORY_NUMERIC:
                $format = '{type}{length}{unsigned}{default}{notnull}{check}{append}';
                break;
            default:
                $format = '{type}{length}{default}{notnull}{check}{append}';
        }

        return $this->buildCompleteString($format);
    }
}
