<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Oracle\Column\BinaryColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\StringColumn;

final class ColumnFactoryProvider extends \Yiisoft\Db\Tests\Provider\ColumnFactoryProvider
{
    public static function dbTypes(): array
    {
        return [
            // db type, expected abstract type, expected instance of
            ['char', ColumnType::CHAR, StringColumn::class],
            ['nchar', ColumnType::CHAR, StringColumn::class],
            ['varchar2', ColumnType::STRING, StringColumn::class],
            ['nvarchar2', ColumnType::STRING, StringColumn::class],
            ['clob', ColumnType::TEXT, StringColumn::class],
            ['nclob', ColumnType::TEXT, StringColumn::class],
            ['long', ColumnType::TEXT, StringColumn::class],
            ['blob', ColumnType::BINARY, BinaryColumn::class],
            ['bfile', ColumnType::BINARY, BinaryColumn::class],
            ['long raw', ColumnType::BINARY, BinaryColumn::class],
            ['raw', ColumnType::BINARY, BinaryColumn::class],
            ['number', ColumnType::DOUBLE, DoubleColumn::class],
            ['binary_float', ColumnType::FLOAT, DoubleColumn::class],
            ['binary_double', ColumnType::DOUBLE, DoubleColumn::class],
            ['float', ColumnType::DOUBLE, DoubleColumn::class],
            ['date', ColumnType::DATE, StringColumn::class],
            ['interval day(0) to second', ColumnType::TIME, StringColumn::class],
            ['timestamp', ColumnType::TIMESTAMP, StringColumn::class],
            ['timestamp with time zone', ColumnType::TIMESTAMP, StringColumn::class],
            ['timestamp with local time zone', ColumnType::TIMESTAMP, StringColumn::class],
        ];
    }

    public static function definitions(): array
    {
        $definitions = parent::definitions();

        $definitions['text'][0] = 'clob';
        $definitions['text'][3]['getDbType'] = 'clob';
        $definitions['text NOT NULL'][0] = 'clob NOT NULL';
        $definitions['text NOT NULL'][3]['getDbType'] = 'clob';
        $definitions['decimal(10,2)'][0] = 'number(10,2)';
        $definitions['decimal(10,2)'][3]['getDbType'] = 'number';

        unset($definitions['bigint UNSIGNED']);

        return $definitions;
    }

    public static function defaultValueRaw(): array
    {
        $defaultValueRaw = parent::defaultValueRaw();

        $defaultValueRaw[] = [ColumnType::STRING, 'NULL ', null];
        $defaultValueRaw[] = [ColumnType::STRING, "'str''ing' ", "str'ing"];
        $defaultValueRaw[] = [ColumnType::INTEGER, '-1 ', -1];
        $defaultValueRaw[] = [ColumnType::TIMESTAMP, 'now() ', new Expression('now()')];

        return $defaultValueRaw;
    }
}
