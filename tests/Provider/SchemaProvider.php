<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Oracle\Column\BinaryColumn;
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
                    'int_col' => [
                        'type' => 'integer',
                        'dbType' => 'number',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => 0,
                        'defaultValue' => null,
                    ],
                    'int_col2' => [
                        'type' => 'integer',
                        'dbType' => 'number',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'tinyint_col' => [
                        'type' => 'integer',
                        'dbType' => 'number',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 3,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'smallint_col' => [
                        'type' => 'integer',
                        'dbType' => 'number',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'char_col' => [
                        'type' => 'char',
                        'dbType' => 'char',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'char_col2' => [
                        'type' => 'string',
                        'dbType' => 'varchar2',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'scale' => null,
                        'defaultValue' => 'some\'thing',
                    ],
                    'char_col3' => [
                        'type' => 'string',
                        'dbType' => 'varchar2',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 4000,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'nvarchar_col' => [
                        'type' => 'string',
                        'dbType' => 'nvarchar2',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'scale' => null,
                        'defaultValue' => '',
                    ],
                    'float_col' => [
                        'type' => 'double',
                        'dbType' => 'float',
                        'phpType' => 'float',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 126,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'float_col2' => [
                        'type' => 'double',
                        'dbType' => 'float',
                        'phpType' => 'float',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 126,
                        'scale' => null,
                        'defaultValue' => 1.23,
                    ],
                    'blob_col' => [
                        'type' => 'binary',
                        'dbType' => 'blob',
                        'phpType' => 'mixed',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'numeric_col' => [
                        'type' => 'decimal',
                        'dbType' => 'number',
                        'phpType' => 'float',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 5,
                        'scale' => 2,
                        'defaultValue' => 33.22,
                    ],
                    'timestamp_col' => [
                        'type' => 'timestamp',
                        'dbType' => 'timestamp',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 6,
                        'scale' => null,
                        'defaultValue' => new Expression("to_timestamp('2002-01-01 00:00:00', 'yyyy-mm-dd hh24:mi:ss')"),
                    ],
                    'time_col' => [
                        'type' => 'time',
                        'dbType' => 'interval day to second',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 0,
                        'scale' => 0,
                        'defaultValue' => new Expression("INTERVAL '0 10:33:21' DAY(0) TO SECOND(0)"),
                    ],
                    'interval_day_col' => [
                        'type' => 'string',
                        'dbType' => 'interval day to second',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 0,
                        'scale' => 1,
                        'defaultValue' => new Expression("INTERVAL '2 04:56:12' DAY(1) TO SECOND(0)"),
                    ],
                    'bool_col' => [
                        'type' => 'char',
                        'dbType' => 'char',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 1,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'bool_col2' => [
                        'type' => 'char',
                        'dbType' => 'char',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 1,
                        'scale' => null,
                        'defaultValue' => '1',
                    ],
                    'ts_default' => [
                        'type' => 'timestamp',
                        'dbType' => 'timestamp',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 6,
                        'scale' => null,
                        'defaultValue' => new Expression('CURRENT_TIMESTAMP'),
                    ],
                    'bit_col' => [
                        'type' => 'integer',
                        'dbType' => 'number',
                        'phpType' => 'int',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 3,
                        'scale' => 0,
                        'defaultValue' => 130, // b'10000010'
                    ],
                    'json_col' => [
                        'type' => 'json',
                        'dbType' => 'clob',
                        'phpType' => 'mixed',
                        'primaryKey' => false,
                        'notNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => null,
                        'defaultValue' => ['a' => 1],
                        'check' => '"json_col" is json',
                    ],
                ],
                'type',
            ],
            [
                [
                    'id' => [
                        'type' => 'integer',
                        'dbType' => 'number',
                        'phpType' => 'int',
                        'primaryKey' => true,
                        'notNull' => true,
                        'autoIncrement' => true,
                        'enumValues' => null,
                        'size' => null,
                        'scale' => 0,
                        'defaultValue' => null,
                    ],
                    'type' => [
                        'type' => 'string',
                        'dbType' => 'varchar2',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'notNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 255,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
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
            [new StringColumn(ColumnType::TIMESTAMP, dbType: 'timestamp', name: 'timestamp_col', notNull: true, size: 6), [
                'oci:decl_type' => 'TIMESTAMP',
                'native_type' => 'TIMESTAMP',
                'pdo_type' => 2,
                'scale' => 6,
                'flags' => ['not_null'],
                'name' => 'timestamp_col',
                'len' => 11,
                'precision' => 0,
            ]],
            [new StringColumn(ColumnType::TIME, dbType: 'interval day to second', name: 'time_col', notNull: false, size: 0), [
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
            [new StringColumn(ColumnType::TIMESTAMP, dbType: 'timestamp with time zone', name: 'TIMESTAMP(3)', notNull: false, size: 3), [
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
