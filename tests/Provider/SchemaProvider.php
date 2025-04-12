<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Oracle\Column\BinaryColumn;
use Yiisoft\Db\Oracle\Column\BooleanColumn;
use Yiisoft\Db\Oracle\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Tests\Support\AnyValue;

final class SchemaProvider extends \Yiisoft\Db\Tests\Provider\SchemaProvider
{
    public static function columns(): array
    {
        return [
            [
                [
                    'int_col' => new IntegerColumn(
                        dbType: 'number',
                        notNull: true,
                        scale: 0,
                    ),
                    'int_col2' => new IntegerColumn(
                        dbType: 'number',
                        scale: 0,
                        defaultValue: 1,
                    ),
                    'tinyint_col' => new IntegerColumn(
                        dbType: 'number',
                        size: 3,
                        scale: 0,
                        defaultValue: 1,
                    ),
                    'smallint_col' => new IntegerColumn(
                        dbType: 'number',
                        scale: 0,
                        defaultValue: 1,
                    ),
                    'char_col' => new StringColumn(
                        ColumnType::CHAR,
                        dbType: 'char',
                        notNull: true,
                        size: 100,
                    ),
                    'char_col2' => new StringColumn(
                        dbType: 'varchar2',
                        size: 100,
                        defaultValue: 'some\'thing',
                    ),
                    'char_col3' => new StringColumn(
                        dbType: 'varchar2',
                        size: 4000,
                    ),
                    'nvarchar_col' => new StringColumn(
                        dbType: 'nvarchar2',
                        size: 100,
                        defaultValue: '',
                    ),
                    'float_col' => new DoubleColumn(
                        dbType: 'float',
                        notNull: true,
                        size: 126,
                    ),
                    'float_col2' => new DoubleColumn(
                        dbType: 'float',
                        size: 126,
                        defaultValue: 1.23,
                    ),
                    'blob_col' => new BinaryColumn(
                        dbType: 'blob',
                    ),
                    'numeric_col' => new DoubleColumn(
                        ColumnType::DECIMAL,
                        dbType: 'number',
                        size: 5,
                        scale: 2,
                        defaultValue: 33.22,
                    ),
                    'timestamp_col' => new StringColumn(
                        ColumnType::TIMESTAMP,
                        dbType: 'timestamp',
                        notNull: true,
                        size: 6,
                        defaultValue: new Expression("to_timestamp('2002-01-01 00:00:00', 'yyyy-mm-dd hh24:mi:ss')"),
                    ),
                    'time_col' => new StringColumn(
                        ColumnType::TIME,
                        dbType: 'interval day to second',
                        size: 0,
                        scale: 0,
                        defaultValue: new Expression("INTERVAL '0 10:33:21' DAY(0) TO SECOND(0)"),
                    ),
                    'interval_day_col' => new StringColumn(
                        dbType: 'interval day to second',
                        size: 0,
                        scale: 1,
                        defaultValue: new Expression("INTERVAL '2 04:56:12' DAY(1) TO SECOND(0)"),
                    ),
                    'bool_col' => new BooleanColumn(
                        dbType: 'char',
                        check: '"bool_col" in (0,1)',
                        notNull: true,
                        size: 1,
                    ),
                    'bool_col2' => new BooleanColumn(
                        dbType: 'char',
                        check: '"bool_col2" in (0,1)',
                        size: 1,
                        defaultValue: true,
                    ),
                    'ts_default' => new StringColumn(
                        ColumnType::TIMESTAMP,
                        dbType: 'timestamp',
                        notNull: true,
                        size: 6,
                        defaultValue: new Expression('CURRENT_TIMESTAMP'),
                    ),
                    'bit_col' => new IntegerColumn(
                        dbType: 'number',
                        notNull: true,
                        size: 3,
                        scale: 0,
                        defaultValue: 130, // b'10000010'
                    ),
                    'json_col' => new JsonColumn(
                        dbType: 'clob',
                        defaultValue: ['a' => 1],
                        check: '"json_col" is json',
                    ),
                ],
                'type',
            ],
            [
                [
                    'id' => new IntegerColumn(
                        dbType: 'number',
                        primaryKey: true,
                        notNull: true,
                        autoIncrement: true,
                        scale: 0,
                    ),
                    'type' => new StringColumn(
                        dbType: 'varchar2',
                        notNull: true,
                        size: 255,
                    ),
                ],
                'animal',
            ],
        ];
    }

    public static function constraints(): array
    {
        $constraints = parent::constraints();

        $constraints['1: check'][2][0]->expression('"C_check" <> \'\'');
        $constraints['1: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_id'])
            ->expression('"C_id" IS NOT NULL');
        $constraints['1: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_not_null'])
            ->expression('"C_not_null" IS NOT NULL');
        $constraints['1: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_unique'])
            ->expression('"C_unique" IS NOT NULL');
        $constraints['1: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_default'])
            ->expression('"C_default" IS NOT NULL');

        $constraints['2: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_id_1'])
            ->expression('"C_id_1" IS NOT NULL');
        $constraints['2: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_id_2'])
            ->expression('"C_id_2" IS NOT NULL');

        $constraints['3: foreign key'][2][0]->foreignSchemaName('SYSTEM');
        $constraints['3: foreign key'][2][0]->onUpdate(null);
        $constraints['3: index'][2] = [];
        $constraints['3: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_fk_id_1'])
            ->expression('"C_fk_id_1" IS NOT NULL');
        $constraints['3: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_fk_id_2'])
            ->expression('"C_fk_id_2" IS NOT NULL');
        $constraints['3: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_id'])
            ->expression('"C_id" IS NOT NULL');

        $constraints['4: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_id'])
            ->expression('"C_id" IS NOT NULL');
        $constraints['4: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_col_2'])
            ->expression('"C_col_2" IS NOT NULL');

        return $constraints;
    }

    public static function tableSchemaWithDbSchemes(): array
    {
        return [
            ['animal', 'animal', 'dbo'],
            ['dbo.animal', 'animal', 'dbo'],
            ['"dbo"."animal"', 'animal', 'dbo'],
            ['"other"."animal2"', 'animal2', 'other',],
            ['other."animal2"', 'animal2', 'other',],
            ['other.animal2', 'animal2', 'other',],
            ['catalog.other.animal2', 'animal2', 'other'],
        ];
    }
}
