<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PDO;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Oracle\Schema;
use Yiisoft\Db\Oracle\TableSchema;
use Yiisoft\Db\TestSupport\AnyValue;
use Yiisoft\Db\TestSupport\TestSchemaTrait;

/**
 * @group oracle
 */
final class SchemaTest extends TestCase
{
    use TestSchemaTrait;

    protected array $expectedSchemas = [];

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
            ],
        ];
    }

    public function testCompositeFk(): void
    {
        $this->markTestSkipped('should be fixed.');
    }

    /**
     * Autoincrement columns detection should be disabled for Oracle because there is no way of associating a column
     * with a sequence.
     */
    public function testAutoincrementDisabled(): void
    {
        $table = $this->getConnection(false)->getSchema()->getTableSchema('order', true);
        $this->assertFalse($table->getColumns()['id']->isAutoIncrement());
    }

    public function testFindUniqueIndexes()
    {
        $db = $this->getConnection();

        try {
            $db->createCommand()->dropTable('uniqueIndex')->execute();
        } catch (\Exception $e) {
        }

        $db->createCommand()->createTable('uniqueIndex', [
            'somecol' => 'string',
            'someCol2' => 'string',
            'someCol3' => 'string',
        ])->execute();

        /* @var $schema Schema */
        $schema = $db->getSchema();

        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
        $this->assertEquals([], $uniqueIndexes);

        $db->createCommand()->createIndex('somecolUnique', 'uniqueIndex', 'somecol', true)->execute();

        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
        $this->assertEquals([
            'somecolUnique' => ['somecol'],
        ], $uniqueIndexes);

        /**
         * Create another column with upper case letter that fails postgres
         * {@see https://github.com/yiisoft/yii2/issues/10613}
         */
        $db->createCommand()->createIndex('someCol2Unique', 'uniqueIndex', 'someCol2', true)->execute();

        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
        $this->assertEquals([
            'somecolUnique' => ['somecol'],
            'someCol2Unique' => ['someCol2'],
        ], $uniqueIndexes);

        /**
         * {@see https://github.com/yiisoft/yii2/issues/13814}
         */
        $db->createCommand()->createIndex('another unique index', 'uniqueIndex', 'someCol3', true)->execute();

        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
        $this->assertEquals([
            'somecolUnique' => ['somecol'],
            'someCol2Unique' => ['someCol2'],
            'another unique index' => ['someCol3'],
        ], $uniqueIndexes);
    }

    public function testGetSchemaNames(): void
    {
        $schema = $this->getConnection()->getSchema();

        $schemas = $schema->getSchemaNames();

        $this->assertNotEmpty($schemas);

        foreach ($this->expectedSchemas as $schema) {
            $this->assertContains($schema, $schemas);
        }
    }

    /**
     * @dataProvider pdoAttributesProviderTrait
     *
     * @param array $pdoAttributes
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testGetTableNames(array $pdoAttributes): void
    {
        $connection = $this->getConnection(true);

        foreach ($pdoAttributes as $name => $value) {
            $connection->getPDO()->setAttribute($name, $value);
        }

        $schema = $connection->getSchema();

        $tables = $schema->getTableNames();

        if ($connection->getDriverName() === 'sqlsrv') {
            $tables = array_map(static function ($item) {
                return trim($item, '[]');
            }, $tables);
        }

        $this->assertContains('customer', $tables);
        $this->assertContains('category', $tables);
        $this->assertContains('item', $tables);
        $this->assertContains('order', $tables);
        $this->assertContains('order_item', $tables);
        $this->assertContains('type', $tables);
        $this->assertContains('animal', $tables);
        $this->assertContains('animal_view', $tables);
    }

    /**
     * @dataProvider pdoAttributesProviderTrait
     *
     * @param array $pdoAttributes
     */
    public function testGetTableSchemas(array $pdoAttributes): void
    {
        $db = $this->getConnection(true);

        foreach ($pdoAttributes as $name => $value) {
            $db->getPDO()->setAttribute($name, $value);
        }

        $schema = $db->getSchema();

        $tables = $schema->getTableSchemas();

        $this->assertCount(count($schema->getTableNames()), $tables);

        foreach ($tables as $table) {
            $this->assertInstanceOf(TableSchema::class, $table);
        }
    }

    public function constraintsProvider()
    {
        $result = $this->constraintsProviderTrait();

        $result['1: check'][2][0]->expression('"C_check" <> \'\'');

        $result['1: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_id'])
            ->expression('"C_id" IS NOT NULL');

        $result['1: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_not_null'])
            ->expression('"C_not_null" IS NOT NULL');

        $result['1: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_unique'])
            ->expression('"C_unique" IS NOT NULL');

        $result['1: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_default'])
            ->expression('"C_default" IS NOT NULL');

        $result['2: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_id_1'])
            ->expression('"C_id_1" IS NOT NULL');

        $result['2: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_id_2'])
            ->expression('"C_id_2" IS NOT NULL');

        $result['3: foreign key'][2][0]->foreignSchemaName('SYSTEM');

        $result['3: foreign key'][2][0]->onUpdate(null);

        $result['3: index'][2] = [];

        $result['3: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_fk_id_1'])
            ->expression('"C_fk_id_1" IS NOT NULL');

        $result['3: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_fk_id_2'])
            ->expression('"C_fk_id_2" IS NOT NULL');

        $result['3: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_id'])
            ->expression('"C_id" IS NOT NULL');

        $result['4: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_id'])
            ->expression('"C_id" IS NOT NULL');

        $result['4: check'][2][] = (new CheckConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_col_2'])
            ->expression('"C_col_2" IS NOT NULL');

        return $result;
    }

    /**
     * @dataProvider constraintsProvider
     *
     * @param string $tableName
     * @param string $type
     * @param mixed $expected
     */
    public function testTableSchemaConstraints(string $tableName, string $type, $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $constraints = $this->getConnection()->getSchema()->{'getTable' . ucfirst($type)}($tableName);

        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider uppercaseConstraintsProviderTrait
     *
     * @param string $tableName
     * @param string $type
     * @param mixed $expected
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $connection = $this->getConnection();

        $connection->getOpenPDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $constraints = $connection->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);

        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider lowercaseConstraintsProviderTrait
     *
     * @param string $tableName
     * @param string $type
     * @param mixed $expected
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $connection = $this->getConnection();

        $connection->getOpenPDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $constraints = $connection->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);

        $this->assertMetadataEquals($expected, $constraints);
    }
}
