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

    public static function boolean(): BooleanColumn
    {
        return new BooleanColumn(ColumnType::BOOLEAN);
    }

    public static function timestamp(int|null $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::TIMESTAMP, size: $size);
    }

    public static function datetime(int|null $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::DATETIME, size: $size);
    }

    public static function datetimeWithTimezone(int|null $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::DATETIMETZ, size: $size);
    }

    public static function time(int|null $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::TIME, size: $size);
    }

    public static function timeWithTimezone(int|null $size = 0): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::TIMETZ, size: $size);
    }

    public static function date(): DateTimeColumn
    {
        return new DateTimeColumn(ColumnType::DATE);
    }

    public static function json(): JsonColumn
    {
        return new JsonColumn(ColumnType::JSON);
    }
}
