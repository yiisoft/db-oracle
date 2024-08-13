<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Yiisoft\Db\Oracle\Column\BinaryColumnSchema;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;

final class ColumnFactoryProvider extends \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider
{
    public static function dbTypes(): array
    {
        return [
            // db type, expected abstract type, expected instance of
            ['char', 'char', StringColumnSchema::class],
            ['nchar', 'char', StringColumnSchema::class],
            ['varchar2', 'string', StringColumnSchema::class],
            ['nvarchar2', 'string', StringColumnSchema::class],
            ['clob', 'text', StringColumnSchema::class],
            ['nclob', 'text', StringColumnSchema::class],
            ['long', 'text', StringColumnSchema::class],
            ['blob', 'binary', BinaryColumnSchema::class],
            ['bfile', 'binary', BinaryColumnSchema::class],
            ['long raw', 'binary', BinaryColumnSchema::class],
            ['raw', 'binary', BinaryColumnSchema::class],
            ['number', 'double', DoubleColumnSchema::class],
            ['binary_float', 'float', DoubleColumnSchema::class],
            ['binary_double', 'double', DoubleColumnSchema::class],
            ['float', 'double', DoubleColumnSchema::class],
            ['date', 'date', StringColumnSchema::class],
            ['interval day(0) to second', 'time', StringColumnSchema::class],
            ['timestamp', 'timestamp', StringColumnSchema::class],
            ['timestamp with time zone', 'timestamp', StringColumnSchema::class],
            ['timestamp with local time zone', 'timestamp', StringColumnSchema::class],
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
