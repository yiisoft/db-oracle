<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

final class ColumnBuilder extends \Yiisoft\Db\Schema\Column\ColumnBuilder
{
    public static function binary(int|null $size = null): ColumnSchemaInterface
    {
        return new BinaryColumnSchema(ColumnType::BINARY, size: $size);
    }
}
