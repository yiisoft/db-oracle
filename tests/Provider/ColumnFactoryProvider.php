<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Oracle\Column\BinaryColumnSchema;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;

final class ColumnFactoryProvider extends \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider
{
    public static function dbTypes(): array
    {
        return [
            // db type, expected abstract type, expected instance of
            ['char', ColumnType::CHAR, StringColumnSchema::class],
            ['nchar', ColumnType::CHAR, StringColumnSchema::class],
            ['varchar2', ColumnType::STRING, StringColumnSchema::class],
            ['nvarchar2', ColumnType::STRING, StringColumnSchema::class],
            ['clob', ColumnType::TEXT, StringColumnSchema::class],
            ['nclob', ColumnType::TEXT, StringColumnSchema::class],
            ['long', ColumnType::TEXT, StringColumnSchema::class],
            ['blob', ColumnType::BINARY, BinaryColumnSchema::class],
            ['bfile', ColumnType::BINARY, BinaryColumnSchema::class],
            ['long raw', ColumnType::BINARY, BinaryColumnSchema::class],
            ['raw', ColumnType::BINARY, BinaryColumnSchema::class],
            ['number', ColumnType::DOUBLE, DoubleColumnSchema::class],
            ['binary_float', ColumnType::FLOAT, DoubleColumnSchema::class],
            ['binary_double', ColumnType::DOUBLE, DoubleColumnSchema::class],
            ['float', ColumnType::DOUBLE, DoubleColumnSchema::class],
            ['date', ColumnType::DATE, StringColumnSchema::class],
            ['interval day(0) to second', ColumnType::TIME, StringColumnSchema::class],
            ['timestamp', ColumnType::TIMESTAMP, StringColumnSchema::class],
            ['timestamp with time zone', ColumnType::TIMESTAMP, StringColumnSchema::class],
            ['timestamp with local time zone', ColumnType::TIMESTAMP, StringColumnSchema::class],
        ];
    }

    public static function definitions(): array
    {
        $definitions = parent::definitions();

        $definitions['text'][0] = 'clob';
        $definitions['text NOT NULL'][0] = 'clob NOT NULL';
        $definitions['decimal(10,2)'][0] = 'number(10,2)';

        unset($definitions['bigint UNSIGNED']);

        return $definitions;
    }
}
