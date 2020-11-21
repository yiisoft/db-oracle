<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\TestUtility\TestSchemaTrait;

/**
 * @group oracle
 */
final class SchemaTest extends TestCase
{
    use TestSchemaTrait;

    public function getExpectedColumns()
    {
        return [
            'int_col' => [
                'type' => 'integer',
                'dbType' => 'NUMBER',
                'phpType' => 'integer',
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
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 22,
                'precision' => null,
                'scale' => 0,
                'defaultValue' => 1,
            ],
            'char_col' => [
                'type' => 'string',
                'dbType' => 'CHAR',
                'phpType' => 'string',
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
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 100,
                'precision' => null,
                'scale' => null,
                'defaultValue' => 'something',
            ],
            'char_col3' => [
                'type' => 'string',
                'dbType' => 'VARCHAR2',
                'phpType' => 'string',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 4000,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
            ],
            'float_col' => [
                'type' => 'double',
                'dbType' => 'FLOAT',
                'phpType' => 'double',
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
                'phpType' => 'string',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 22,
                'precision' => 5,
                'scale' => 2,
                'defaultValue' => '33.22',
            ],
            'time' => [
                'type' => 'timestamp',
                'dbType' => 'TIMESTAMP(6)',
                'phpType' => 'string',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 11,
                'precision' => null,
                'scale' => 6,
                'defaultValue' => null,
            ],
            'bool_col' => [
                'type' => 'string',
                'dbType' => 'CHAR',
                'phpType' => 'string',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 1,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
            ],
            'bool_col2' => [
                'type' => 'string',
                'dbType' => 'CHAR',
                'phpType' => 'string',
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
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 11,
                'precision' => null,
                'scale' => 6,
                'defaultValue' => null,
            ],
            'bit_col' => [
                'type' => 'string',
                'dbType' => 'CHAR',
                'phpType' => 'string',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 3,
                'precision' => null,
                'scale' => null,
                'defaultValue' => '130', // b'10000010'
            ]
        ];
    }

    public function testCompositeFk(): void
    {
        $this->markTestSkipped('should be fixed.');
    }
}
