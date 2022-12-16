<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Oracle\Schema;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonSchemaTest;
use Yiisoft\Db\Tests\Support\DbHelper;

/**
 * @group oracle
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class SchemaTest extends CommonSchemaTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\SchemaProvider::columns()
     */
    public function testColumnSchema(array $columns): void
    {
        parent::testColumnSchema($columns);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testCompositeFk(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $table = $schema->getTableSchema('composite_fk');

        $this->assertNotNull($table);

        $fk = $table->getForeignKeys();

        $this->assertCount(1, $fk);
        $this->assertSame('order_item', $fk[0][0]);
        $this->assertSame('order_id', $fk[0]['order_id']);
        $this->assertSame('item_id', $fk[0]['item_id']);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testGetDefaultSchema(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertSame('SYSTEM', $schema->getDefaultSchema());
    }

    public function testGetSchemaDefaultValues(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Oracle\Schema::loadTableDefaultValues is not supported by Oracle.');

        parent::testGetSchemaDefaultValues();
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\SchemaProvider::columnsTypeChar()
     */
    public function testGetStringFieldsSize(
        string $columnName,
        string $columnType,
        int|null $columnSize,
        string $columnDbType
    ): void {
        parent::testGetStringFieldsSize($columnName, $columnType, $columnSize, $columnDbType);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testGetSchemaNames(): void
    {
        $db = $this->getConnection(true);

        $expectedSchemas = ['HR'];
        $schema = $db->getSchema();
        $schemas = $schema->getSchemaNames();

        $this->assertNotEmpty($schemas);

        foreach ($expectedSchemas as $schema) {
            $this->assertContains($schema, $schemas);
        }
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testGetTableNamesWithSchema(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $tablesNames = $schema->getTableNames('HR');

        $expectedTableNames = [
            'COUNTRIES',
            'DEPARTMENTS',
            'EMPLOYEES',
            'EMP_DETAILS_VIEW',
            'JOBS',
            'JOB_HISTORY',
            'LOCATIONS',
            'REGIONS',
        ];

        $this->assertSame($expectedTableNames, $tablesNames);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testGetViewNames(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $views = $schema->getViewNames();

        $this->assertContains('animal_view', $views);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testGetViewNamesWithSchema(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $views = $schema->getViewNames('SYSTEM');

        $this->assertContains('animal_view', $views);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\SchemaProvider::constraints()
     *
     * @throws Exception
     */
    public function testTableSchemaConstraints(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraints($tableName, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\SchemaProvider::constraints()
     *
     * @throws Exception
     */
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoLowercase($tableName, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\SchemaProvider::constraints()
     *
     * @throws Exception
     */
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, mixed $expected): void
    {
        parent::testTableSchemaConstraintsWithPdoUppercase($tableName, $type, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\SchemaProvider::tableSchemaWithDbSchemes()
     *
     * @throws Exception
     */
    public function testTableSchemaWithDbSchemes(
        string $tableName,
        string $expectedTableName,
        string $expectedSchemaName = ''
    ): void {
        $db = $this->getConnection();

        $commandMock = $this->createMock(CommandInterface::class);
        $commandMock->method('queryAll')->willReturn([]);
        $mockDb = $this->createMock(ConnectionInterface::class);
        $mockDb->method('getQuoter')->willReturn($db->getQuoter());
        $mockDb
            ->method('createCommand')
            ->with(
                self::callback(static fn ($sql) => true),
                self::callback(
                    function ($params) use ($expectedTableName, $expectedSchemaName) {
                        $this->assertEquals($expectedTableName, $params[':tableName']);
                        $this->assertEquals($expectedSchemaName, $params[':schemaName']);

                        return true;
                    }
                )
            )
            ->willReturn($commandMock);
        $schema = new Schema($mockDb, DbHelper::getSchemaCache(), 'dbo');
        $schema->getTablePrimaryKey($tableName);
    }
}
