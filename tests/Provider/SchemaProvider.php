<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Expression\Expression;
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
                        'dbType' => 'NUMBER',
                        'phpType' => 'integer',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 22,
                        'precision' => null,
                        'scale' => 0,
                        'defaultValue' => null,
                    ],
                    'int_col2' => [
                        'type' => 'integer',
                        'dbType' => 'NUMBER',
                        'phpType' => 'integer',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 22,
                        'precision' => null,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'tinyint_col' => [
                        'type' => 'integer',
                        'dbType' => 'NUMBER',
                        'phpType' => 'integer',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 22,
                        'precision' => 3,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'smallint_col' => [
                        'type' => 'integer',
                        'dbType' => 'NUMBER',
                        'phpType' => 'integer',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 22,
                        'precision' => null,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'char_col' => [
                        'type' => 'char',
                        'dbType' => 'CHAR',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'char_col2' => [
                        'type' => 'string',
                        'dbType' => 'VARCHAR2',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => 'some\'thing',
                    ],
                    'char_col3' => [
                        'type' => 'string',
                        'dbType' => 'VARCHAR2',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 4000,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'nvarchar_col' => [
                        'type' => 'string',
                        'dbType' => 'NVARCHAR2',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => '',
                    ],
                    'float_col' => [
                        'type' => 'double',
                        'dbType' => 'FLOAT',
                        'phpType' => 'double',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 22,
                        'precision' => 126,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'float_col2' => [
                        'type' => 'double',
                        'dbType' => 'FLOAT',
                        'phpType' => 'double',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 22,
                        'precision' => 126,
                        'scale' => null,
                        'defaultValue' => 1.23,
                    ],
                    'blob_col' => [
                        'type' => 'binary',
                        'dbType' => 'BLOB',
                        'phpType' => 'resource',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 4000,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'numeric_col' => [
                        'type' => 'decimal',
                        'dbType' => 'NUMBER',
                        'phpType' => 'double',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 22,
                        'precision' => 5,
                        'scale' => 2,
                        'defaultValue' => 33.22,
                    ],
                    'timestamp_col' => [
                        'type' => 'timestamp',
                        'dbType' => 'TIMESTAMP(6)',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 11,
                        'precision' => null,
                        'scale' => 6,
                        'defaultValue' => "to_timestamp('2002-01-01 00:00:00', 'yyyy-mm-dd hh24:mi:ss')",
                    ],
                    'time_col' => [
                        'type' => 'time',
                        'dbType' => 'INTERVAL DAY(0) TO SECOND(0)',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 11,
                        'precision' => 0,
                        'scale' => 0,
                        'defaultValue' => "INTERVAL '0 10:33:21' DAY(0) TO SECOND(0)",
                    ],
                    'bool_col' => [
                        'type' => 'char',
                        'dbType' => 'CHAR',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 1,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'bool_col2' => [
                        'type' => 'char',
                        'dbType' => 'CHAR',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 1,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => '1',
                    ],
                    'ts_default' => [
                        'type' => 'timestamp',
                        'dbType' => 'TIMESTAMP(6)',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 11,
                        'precision' => null,
                        'scale' => 6,
                        'defaultValue' => new Expression('CURRENT_TIMESTAMP'),
                    ],
                    'bit_col' => [
                        'type' => 'integer',
                        'dbType' => 'NUMBER',
                        'phpType' => 'integer',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 3,
                        'scale' => 0,
                        'defaultValue' => 130, // b'10000010'
                    ],
                ],
                'type',
            ],
            [
                [
                    'id' => [
                        'type' => 'integer',
                        'dbType' => 'NUMBER',
                        'phpType' => 'integer',
                        'primaryKey' => true,
                        'allowNull' => false,
                        'autoIncrement' => true,
                        'enumValues' => null,
                        'size' => 22,
                        'precision' => null,
                        'scale' => 0,
                        'defaultValue' => null,
                    ],
                    'type' => [
                        'type' => 'string',
                        'dbType' => 'VARCHAR2',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 255,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                ],
                'animal',
            ],
        ];
    }

    public static function columnsTypeChar(): array
    {
        return [
            ['char_col', 'char', 100, 'CHAR'],
            ['char_col2', 'string', 100, 'VARCHAR2'],
            ['char_col3', 'string', 4000, 'VARCHAR2'],
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
