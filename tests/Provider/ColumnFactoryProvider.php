<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Oracle\Column\BinaryColumn;
use Yiisoft\Db\Oracle\Column\BooleanColumn;
use Yiisoft\Db\Oracle\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\ArrayColumn;
use Yiisoft\Db\Schema\Column\BigIntColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
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
            ['timestamp', ColumnType::TIMESTAMP, StringColumn::class],
            ['timestamp with time zone', ColumnType::TIMESTAMP, StringColumn::class],
            ['timestamp with local time zone', ColumnType::TIMESTAMP, StringColumn::class],
            ['timestamp with local time zone', ColumnType::TIMESTAMP, StringColumn::class],
            ['interval day to second', ColumnType::STRING, StringColumn::class],
            ['interval year to month', ColumnType::STRING, StringColumn::class],
        ];
    }

    public static function definitions(): array
    {
        $definitions = parent::definitions();

        $definitions['text'][0] = 'clob';
        $definitions['text'][1]->dbType('clob');
        $definitions['text NOT NULL'][0] = 'clob NOT NULL';
        $definitions['text NOT NULL'][1]->dbType('clob');
        $definitions['decimal(10,2)'][0] = 'number(10,2)';
        $definitions['decimal(10,2)'][1]->dbType('number');
        $definitions['bigint UNSIGNED'][1] = new BigIntColumn(unsigned: true);
        $definitions['integer[]'] = ['number(10,0)[]', new ArrayColumn(dbType: 'number', size: 10, column: new IntegerColumn(dbType: 'number', size: 10))];

        return [
            ...$definitions,
            ['interval day to second', new StringColumn(dbType: 'interval day to second')],
            ['interval day(0) to second', new StringColumn(ColumnType::TIME, dbType: 'interval day to second', scale: 0)],
            ['interval day (0) to second(6)', new StringColumn(ColumnType::TIME, dbType: 'interval day to second', scale: 0, size: 6)],
            ['interval day to second (0)', new StringColumn(dbType: 'interval day to second', size: 0)],
            ['interval year to month', new StringColumn(dbType: 'interval year to month')],
            ['interval year (2) to month', new StringColumn(dbType: 'interval year to month', scale: 2)],
        ];
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

    public static function types(): array
    {
        $types = parent::types();

        $types['binary'][2] = BinaryColumn::class;
        $types['boolean'][2] = BooleanColumn::class;
        $types['json'][2] = JsonColumn::class;

        return $types;
    }
}
