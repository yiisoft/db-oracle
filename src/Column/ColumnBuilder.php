<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

final class ColumnBuilder extends \Yiisoft\Db\Schema\Column\ColumnBuilder
{
    public static function tinyint(int|null $size = 3): ColumnSchemaInterface
    {
        return parent::tinyint($size);
    }

    public static function smallint(int|null $size = 5): ColumnSchemaInterface
    {
        return parent::smallint($size);
    }

    public static function integer(int|null $size = 10): ColumnSchemaInterface
    {
        return parent::integer($size);
    }

    public static function bigint(int|null $size = 20): ColumnSchemaInterface
    {
        return parent::bigint($size);
    }

    public static function binary(int|null $size = null): ColumnSchemaInterface
    {
        return new BinaryColumnSchema(ColumnType::BINARY, size: $size);
    }
}
