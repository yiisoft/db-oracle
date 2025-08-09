<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use DateTimeImmutable;
use DateTimeZone;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constraint\Check;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Oracle\Column\BinaryColumn;
use Yiisoft\Db\Oracle\Column\BooleanColumn;
use Yiisoft\Db\Oracle\Column\DateTimeColumn;
use Yiisoft\Db\Oracle\Column\JsonColumn;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Tests\Support\Assert;

final class SchemaProvider extends \Yiisoft\Db\Tests\Provider\SchemaProvider
{
    use TestTrait;

    public static function columns(): array
    {
        $db = self::getDb();
        $dbTimezone = self::getDb()->getServerInfo()->getTimezone();
        $db->close();

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
                        collation: 'USING_NLS_COMP',
                    ),
                    'char_col2' => new StringColumn(
                        dbType: 'varchar2',
                        size: 100,
                        collation: 'USING_NLS_COMP',
                        defaultValue: 'some\'thing',
                    ),
                    'char_col3' => new StringColumn(
                        dbType: 'varchar2',
                        size: 4000,
                        collation: 'USING_NLS_COMP',
                    ),
                    'nvarchar_col' => new StringColumn(
                        dbType: 'nvarchar2',
                        size: 100,
                        collation: 'USING_NLS_COMP',
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
                    'timestamp_col' => new DateTimeColumn(
                        dbType: 'timestamp',
                        notNull: true,
                        size: 6,
                        defaultValue: new DateTimeImmutable('2002-01-01 00:00:00', new DateTimeZone('UTC')),
                        shouldConvertTimezone: true,
                    ),
                    'timestamp_local' => new DateTimeColumn(
                        dbType: 'timestamp with local time zone',
                        size:6,
                        dbTimezone: $dbTimezone,
                    ),
                    'time_col' => new DateTimeColumn(
                        ColumnType::TIME,
                        dbType: 'interval day to second',
                        size: 0,
                        scale: 0,
                        defaultValue: new DateTimeImmutable('10:33:21', new DateTimeZone('UTC')),
                        shouldConvertTimezone: true,
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
                    'ts_default' => new DateTimeColumn(
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
                        collation: 'USING_NLS_COMP',
                    ),
                ],
                'animal',
            ],
        ];
    }

    public static function constraints(): array
    {
        $constraints = parent::constraints();

        Assert::setPropertyValue($constraints['1: check'][2][0], 'expression', '"C_check" <> \'\'');
        $constraints['1: check'][2][] = new Check('', ['C_id'], '"C_id" IS NOT NULL');
        $constraints['1: check'][2][] = new Check('', ['C_not_null'], '"C_not_null" IS NOT NULL');
        $constraints['1: check'][2][] = new Check('', ['C_unique'], '"C_unique" IS NOT NULL');
        $constraints['1: check'][2][] = new Check('', ['C_default'], '"C_default" IS NOT NULL');

        $constraints['2: check'][2][] = new Check('', ['C_id_1'], '"C_id_1" IS NOT NULL');
        $constraints['2: check'][2][] = new Check('', ['C_id_2'], '"C_id_2" IS NOT NULL');

        Assert::setPropertyValue($constraints['3: foreign key'][2][0], 'foreignSchemaName', 'SYSTEM');
        Assert::setPropertyValue($constraints['3: foreign key'][2][0], 'foreignTableName', 'T_constraints_2');
        Assert::setPropertyValue($constraints['3: foreign key'][2][0], 'onUpdate', null);
        $constraints['3: index'][2] = [];
        $constraints['3: check'][2][] = new Check('', ['C_fk_id_1'], '"C_fk_id_1" IS NOT NULL');
        $constraints['3: check'][2][] = new Check('', ['C_fk_id_2'], '"C_fk_id_2" IS NOT NULL');
        $constraints['3: check'][2][] = new Check('', ['C_id'], '"C_id" IS NOT NULL');

        $constraints['4: check'][2][] = new Check('', ['C_id'], '"C_id" IS NOT NULL');
        $constraints['4: check'][2][] = new Check('', ['C_col_2'], '"C_col_2" IS NOT NULL');

        return $constraints;
    }

    public static function resultColumns(): array
    {
        return [
            [null, []],
            [null, ['oci:decl_type' => '']],
            [new IntegerColumn(dbType: 'number', name: 'int_col', notNull: true, size: 38, scale: 0), [
                'oci:decl_type' => 'NUMBER',
                'native_type' => 'NUMBER',
                'pdo_type' => 2,
                'scale' => 0,
                'flags' => ['not_null'],
                'name' => 'int_col',
                'len' => 22,
                'precision' => 38,
            ]],
            [new IntegerColumn(dbType: 'number', name: 'tinyint_col', notNull: false, size: 3, scale: 0), [
                'oci:decl_type' => 'NUMBER',
                'native_type' => 'NUMBER',
                'pdo_type' => 2,
                'scale' => 0,
                'flags' => ['nullable'],
                'name' => 'tinyint_col',
                'len' => 22,
                'precision' => 3,
            ]],
            [new StringColumn(ColumnType::CHAR, dbType: 'char', name: 'char_col', notNull: true, size: 100), [
                'oci:decl_type' => 'CHAR',
                'native_type' => 'CHAR',
                'pdo_type' => 2,
                'scale' => 0,
                'flags' => ['not_null'],
                'name' => 'char_col',
                'len' => 100,
                'precision' => 0,
            ]],
            [new StringColumn(dbType: 'varchar2', name: 'char_col2', notNull: false, size: 100), [
                'oci:decl_type' => 'VARCHAR2',
                'native_type' => 'VARCHAR2',
                'pdo_type' => 2,
                'scale' => 0,
                'flags' => ['nullable'],
                'name' => 'char_col2',
                'len' => 100,
                'precision' => 0,
            ]],
            [new DoubleColumn(dbType: 'float', name: 'float_col', notNull: true, size: 126), [
                'oci:decl_type' => 'FLOAT',
                'native_type' => 'FLOAT',
                'pdo_type' => 2,
                'scale' => -127,
                'flags' => ['not_null'],
                'name' => 'float_col',
                'len' => 22,
                'precision' => 126,
            ]],
            [new BinaryColumn(dbType: 'blob', name: 'blob_col', notNull: false, size: 4000), [
                'oci:decl_type' => 'BLOB',
                'native_type' => 'BLOB',
                'pdo_type' => 3,
                'scale' => 0,
                'flags' => ['blob', 'nullable'],
                'name' => 'blob_col',
                'len' => 4000,
                'precision' => 0,
            ]],
            [new DoubleColumn(ColumnType::DECIMAL, dbType: 'number', name: 'numeric_col', notNull: false, size: 5, scale: 2), [
                'oci:decl_type' => 'NUMBER',
                'native_type' => 'NUMBER',
                'pdo_type' => 2,
                'scale' => 2,
                'flags' => ['nullable'],
                'name' => 'numeric_col',
                'len' => 22,
                'precision' => 5,
            ]],
            [new DateTimeColumn(dbType: 'timestamp', name: 'timestamp_col', notNull: true, size: 6), [
                'oci:decl_type' => 'TIMESTAMP',
                'native_type' => 'TIMESTAMP',
                'pdo_type' => 2,
                'scale' => 6,
                'flags' => ['not_null'],
                'name' => 'timestamp_col',
                'len' => 11,
                'precision' => 0,
            ]],
            [new DateTimeColumn(ColumnType::TIME, dbType: 'interval day to second', name: 'time_col', notNull: false, size: 0), [
                'oci:decl_type' => 'INTERVAL DAY TO SECOND',
                'native_type' => 'INTERVAL DAY TO SECOND',
                'pdo_type' => 2,
                'scale' => 0,
                'flags' => ['nullable'],
                'name' => 'time_col',
                'len' => 11,
                'precision' => 0,
            ]],
            [new BinaryColumn(dbType: 'clob', name: 'json_col', notNull: false, size: 4000), [
                'oci:decl_type' => 'CLOB',
                'native_type' => 'CLOB',
                'pdo_type' => 3,
                'scale' => 0,
                'flags' => ['blob', 'nullable'],
                'name' => 'json_col',
                'len' => 4000,
                'precision' => 0,
            ]],
            [new JsonColumn(dbType: 'json', name: 'json_col', notNull: false, size: 8200), [
                'oci:decl_type' => 119,
                'native_type' => 'UNKNOWN',
                'pdo_type' => 2,
                'scale' => 0,
                'flags' => ['nullable'],
                'name' => 'json_col',
                'len' => 8200,
                'precision' => 0,
            ]],
            [new StringColumn(dbType: 'varchar2', name: 'NULL', notNull: false), [
                'oci:decl_type' => 'VARCHAR2',
                'native_type' => 'VARCHAR2',
                'pdo_type' => 2,
                'scale' => 0,
                'flags' => ['nullable'],
                'name' => 'NULL',
                'len' => 0,
                'precision' => 0,
            ]],
            [new DoubleColumn(dbType: 'number', name: '1', notNull: false), [
                'oci:decl_type' => 'NUMBER',
                'native_type' => 'NUMBER',
                'pdo_type' => 2,
                'scale' => -127,
                'flags' => ['nullable'],
                'name' => '1',
                'len' => 2,
                'precision' => 0,
            ]],
            [new StringColumn(ColumnType::CHAR, dbType: 'char', name: "'STRING'", notNull: false, size: 6), [
                'oci:decl_type' => 'CHAR',
                'native_type' => 'CHAR',
                'pdo_type' => 2,
                'scale' => 0,
                'flags' => ['nullable'],
                'name' => "'STRING'",
                'len' => 6,
                'precision' => 0,
            ]],
            [new DateTimeColumn(ColumnType::DATETIMETZ, dbType: 'timestamp with time zone', name: 'TIMESTAMP(3)', notNull: false, size: 3), [
                'oci:decl_type' => 'TIMESTAMP WITH TIMEZONE',
                'native_type' => 'TIMESTAMP WITH TIMEZONE',
                'pdo_type' => 2,
                'scale' => 3,
                'flags' => ['nullable'],
                'name' => 'TIMESTAMP(3)',
                'len' => 13,
                'precision' => 0,
            ]],
        ];
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
