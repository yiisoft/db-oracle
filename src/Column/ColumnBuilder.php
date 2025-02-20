<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Column;

use Yiisoft\Db\Constant\ColumnType;

final class ColumnBuilder extends \Yiisoft\Db\Schema\Column\ColumnBuilder
{
    public static function binary(int|null $size = null): BinaryColumn
    {
        return new BinaryColumn(ColumnType::BINARY, size: $size);
    }

    public static function json(): JsonColumn
    {
        return new JsonColumn(ColumnType::JSON);
    }
}
