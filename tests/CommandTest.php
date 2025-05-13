<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use ReflectionException;
use Throwable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Oracle\Column\ColumnBuilder;
use Yiisoft\Db\Oracle\IndexType;
use Yiisoft\Db\Oracle\Tests\Provider\CommandProvider;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Common\CommonCommandTest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Transaction\TransactionInterface;

use function is_resource;
use function str_pad;
use function stream_get_contents;
use function version_compare;

/**
 * @group oracle
 */
final class CommandTest extends CommonCommandTest
{
    use TestTrait;

    protected string $upsertTestCharCast = 'CAST([[address]] AS VARCHAR(255))';

    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Oracle\DDLQueryBuilder::addDefaultValue is not supported by Oracle.'
        );

        $command->addDefaultValue('{{table}}', '{{name}}', 'column', 'value');

        $db->close();
    }

    #[DataProviderExternal(CommandProvider::class, 'batchInsert')]
    public function testBatchInsert(
        string $table,
        iterable $values,
        array $columns,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        $db = $this->getConnection();

        if (version_compare($db->getServerInfo()->getVersion(), '21', '>=')) {
            $this->fixture = 'oci21.sql';
        }

        $db->close();

        parent::testBatchInsert($table, $values, $columns, $expected, $expectedParams, $insertedRow);
    }

    /** @link https://github.com/yiisoft/db-oracle/issues/284 */
    public function testBatchInsertWithAutoincrement(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        try {
            $command->dropTable('test_batch_autoincrement')->execute();
        } catch (Exception) {
        }

        $command->createTable('test_batch_autoincrement', [
            'id' => PseudoType::PK,
            'name' => ColumnType::STRING,
        ])->execute();

        $command->insertBatch('test_batch_autoincrement', [['name' => 'John'], ['name' => 'Emma']])->execute();

        $this->assertSame(
            [
                ['id' => '1', 'name' => 'John'],
                ['id' => '2', 'name' => 'Emma'],
            ],
            (new Query($db))->from('test_batch_autoincrement')->all()
        );

        $db->close();
    }

    public function testCLOBStringInsertion(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('longstring') !== null) {
            $command->dropTable('longstring')->execute();
        }

        $command->createTable('longstring', ['message' => ColumnType::TEXT])->execute();
        $longData = str_pad('-', 4001, '-=', STR_PAD_LEFT);
        $command->insert('longstring', ['message' => $longData])->execute();

        $this->assertSame(
            '1',
            $command->setSql(
                <<<SQL
                SELECT count(*) FROM [[longstring]]
                SQL,
            )->queryScalar(),
        );

        $command->dropTable('longstring')->execute();

        $db->close();
    }

    public function testCreateTable(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('testCreateTable') !== null) {
            $command->setSql(
                <<<SQL
                DROP SEQUENCE testCreateTable_SEQ
                SQL,
            )->execute();
            $command->dropTable('testCreateTable')->execute();
        }

        $command->createTable(
            '{{testCreateTable}}',
            ['id' => PseudoType::PK, 'bar' => ColumnType::INTEGER]
        )->execute();
        $command->setSql(
            <<<SQL
            CREATE SEQUENCE testCreateTable_SEQ START with 1 INCREMENT BY 1
            SQL,
        )->execute();
        $command->setSql(
            <<<SQL
            INSERT INTO [[testCreateTable]] ("id", "bar") VALUES(testCreateTable_SEQ.NEXTVAL, 1)
            SQL,
        )->execute();
        $records = $command->setSql(
            <<<SQL
            SELECT [[id]], [[bar]] FROM [[testCreateTable]]
            SQL,
        )->queryAll();

        $this->assertSame([['id' => '1', 'bar' => '1']], $records);

        $db->close();
    }

    public function testCreateView(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('testCreateView') !== null) {
            $command->dropView('testCreateView')->execute();
        }

        if ($schema->getTableSchema('testCreateViewTable') !== null) {
            $command->setSql(
                <<<SQL
                DROP SEQUENCE testCreateViewTable_SEQ
                SQL,
            )->execute();
            $command->dropTable('testCreateViewTable')->execute();
        }

        $subquery = (new Query($db))->select('{{bar}}')->from('{{testCreateViewTable}}')->where(['>', '{{bar}}', '5']);
        $command->createTable(
            '{{testCreateViewTable}}',
            [
                '[[id]]' => PseudoType::PK,
                '[[bar]]' => ColumnType::INTEGER,
            ],
        )->execute();
        $command->setSql(
            <<<SQL
            CREATE SEQUENCE testCreateViewTable_SEQ START with 1 INCREMENT BY 1
            SQL,
        )->execute();
        $command->setSql(
            <<<SQL
            INSERT INTO [[testCreateViewTable]] ("id", "bar") VALUES(testCreateViewTable_SEQ.NEXTVAL, 1)
            SQL,
        )->execute();
        $command->setSql(
            <<<SQL
            INSERT INTO [[testCreateViewTable]] ("id", "bar") VALUES(testCreateViewTable_SEQ.NEXTVAL, 6)
            SQL,
        )->execute();
        $command->createView('{{testCreateView}}', $subquery)->execute();

        $records = $db->createCommand(
            <<<SQL
            SELECT [[bar]] FROM [[testCreateView]]
            SQL,
        )->queryAll();

        $this->assertSame([['bar' => '6']], $records);

        $command->dropView('testCreateView')->execute();

        $db->close();
    }

    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Oracle\DDLQueryBuilder::dropDefaultValue is not supported by Oracle.'
        );

        $command->dropDefaultValue('{{table}}', '{{name}}');
    }

    public function testDropTableIfExists(): void
    {
        $command = $this->getConnection()->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Oracle doesn\'t support "IF EXISTS" option on drop table.');
        $command->dropTable('{{table}}', ifExists: true);
    }

    public function testDropTableIfExistsWithExistTable(): void
    {
        $this->markTestSkipped('Oracle doesn\'t support "IF EXISTS" option on drop table.');
    }

    public function testDropTableIfExistsWithNonExistTable(): void
    {
        $this->markTestSkipped('Oracle doesn\'t support "IF EXISTS" option on drop table.');
    }

    public function testExecuteWithTransaction(): void
    {
        $db = $this->getConnection(true);

        $this->assertNull($db->getTransaction());

        $command = $db->createCommand(
            <<<SQL
            INSERT INTO {{profile}} ([[description]]) VALUES('command transaction 1')
            SQL,
        );

        Assert::invokeMethod($command, 'requireTransaction');

        $command->execute();

        $this->assertNull($db->getTransaction());

        $this->assertEquals(
            1,
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'command transaction 1'
                SQL,
            )->queryScalar(),
        );

        $command = $db->createCommand(
            <<<SQL
            INSERT INTO {{profile}} ([[description]]) VALUES('command transaction 2')
            SQL,
        );

        Assert::invokeMethod($command, 'requireTransaction', [TransactionInterface::READ_COMMITTED]);

        $command->execute();

        $this->assertNull($db->getTransaction());

        $this->assertEquals(
            1,
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'command transaction 2'
                SQL,
            )->queryScalar(),
        );

        $db->close();
    }

    #[DataProviderExternal(CommandProvider::class, 'rawSql')]
    public function testGetRawSql(string $sql, array $params, string $expectedRawSql): void
    {
        parent::testGetRawSql($sql, $params, $expectedRawSql);
    }

    public function testsInsertQueryAsColumnValue(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $time = (string) time();

        $command->delete('{{order_with_null_fk}}')->execute();
        $command->insert('{{order}}', ['customer_id' => 1, 'created_at' => $time, 'total' => 42])->execute();
        $columnValueQuery = new Query($db);
        $orderId = $db->getLastInsertId('order_SEQ');
        $columnValueQuery->select('created_at')->from('{{order}}')->where(['id' => $orderId]);
        $command->insert(
            '{{order_with_null_fk}}',
            [
                'customer_id' => $orderId,
                'created_at' => $columnValueQuery,
                'total' => 42,
            ],
        )->execute();

        $this->assertSame(
            $time,
            $command->setSql(
                <<<SQL
                SELECT [[created_at]] FROM {{order_with_null_fk}} WHERE [[customer_id]] = :orderId
                SQL,
            )->bindValues([':orderId' => $orderId])->queryScalar(),
        );

        $command->delete('{{order_with_null_fk}}')->execute();
        $command->delete('{{order}}', ['id' => $orderId])->execute();

        $db->close();
    }

    public function testInsertWithReturningPksWithPrimaryKeyString(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{test_insert_pk}}') !== null) {
            $command->dropTable('{{test_insert_pk}}')->execute();
        }

        $command->createTable(
            '{{test_insert_pk}}',
            ['id' => 'varchar(10) primary key', 'name' => 'varchar(10)'],
        )->execute();

        $result = $command->insertWithReturningPks('{{test_insert_pk}}', ['id' => '1', 'name' => 'test']);

        $this->assertSame(['id' => '1'], $result);

        $db->close();
    }

    public function testInsertWithReturningPksWithPrimaryKeySignedDecimal(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('{{test_insert_pk}}') !== null) {
            $command->dropTable('{{test_insert_pk}}')->execute();
        }

        $command->createTable(
            '{{test_insert_pk}}',
            ['id' => 'number(5,2) primary key', 'name' => 'varchar(10)'],
        )->execute();

        $result = $command->insertWithReturningPks('{{test_insert_pk}}', ['id' => '-123.45', 'name' => 'test']);

        $this->assertSame(['id' => '-123.45'], $result);

        $db->close();
    }

    public function testInsertSelectAlias(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $command->delete('{{customer}}')->execute();
        $command->insert(
            '{{customer}}',
            [
                'email' => 't1@example.com',
                'name' => 'test',
                'address' => 'test address',
            ]
        )->execute();

        $query = $command->setSql(
            <<<SQL
            SELECT 't2@example.com' AS [[email]], [[address]] AS [[name]], [[name]] AS [[address]] FROM [[customer]]
            SQL,
        );
        $row = $query->queryOne();

        $this->assertIsArray($row);

        $command->insert('{{customer}}', $row)->execute();

        $this->assertEquals(2, $command->setSql(
            <<<SQL
            SELECT COUNT(*) FROM [[customer]]
            SQL,
        )->queryScalar());

        $record = $command->setSql(
            <<<SQL
            SELECT [[email]], [[name]], [[address]] FROM {{customer}}
            SQL,
        )->queryAll();

        $this->assertEquals([
            [
                'email' => 't1@example.com',
                'name' => 'test',
                'address' => 'test address',
            ],
            [
                'email' => 't2@example.com',
                'name' => 'test address',
                'address' => 'test',
            ],
        ], $record);

        $db->close();
    }

    #[DataProviderExternal(CommandProvider::class, 'insertVarbinary')]
    public function testInsertVarbinary(mixed $expectedData, mixed $testData): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->delete('{{T_upsert_varbinary}}')->execute();
        $command->insert('{{T_upsert_varbinary}}', ['id' => 1, 'blob_col' => $testData])->execute();
        $query = (new Query($db))->select(['blob_col'])->from('{{T_upsert_varbinary}}')->where(['id' => 1]);
        $resultData = $query->createCommand()->queryOne();

        $this->assertIsArray($resultData);

        /** @var mixed $resultBlob */
        $resultBlob = is_resource($resultData['blob_col']) ? stream_get_contents($resultData['blob_col']) : $resultData['blob_col'];

        $this->assertSame($expectedData, $resultBlob);

        $db->close();
    }

    public function testNoTablenameReplacement(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->insert(
            '{{customer}}',
            [
                'name' => 'Some {{weird}} name',
                'email' => 'test@example.com',
                'address' => 'Some {{%weird}} address',
            ],
        )->execute();

        $customerId = $db->getLastInsertId('customer_SEQ');

        $customer = $command->setSql(
            <<<SQL
            SELECT * FROM {{customer}} WHERE
            SQL . ' [[id]] = ' . $customerId,
        )->queryOne();

        $this->assertIsArray($customer);
        $this->assertSame('Some {{weird}} name', $customer['name']);
        $this->assertSame('Some {{%weird}} address', $customer['address']);

        $command->update(
            '{{customer}}',
            [
                'name' => 'Some {{updated}} name',
                'address' => 'Some {{%updated}} address',
            ],
            ['id' => $customerId]
        )->execute();

        $customer = $command->setSql(
            <<<SQL
            SELECT * FROM {{customer}} WHERE
            SQL . ' [[id]] = ' . $customerId
        )->queryOne();

        $this->assertIsArray($customer);
        $this->assertSame('Some {{updated}} name', $customer['name']);
        $this->assertSame('Some {{%updated}} address', $customer['address']);

        $db->close();
    }

    #[DataProviderExternal(CommandProvider::class, 'update')]
    public function testUpdate(
        string $table,
        array $columns,
        array|string $conditions,
        array $params,
        array $expectedValues,
        int $expectedCount,
    ): void {
        parent::testUpdate($table, $columns, $conditions, $params, $expectedValues, $expectedCount);
    }

    #[DataProviderExternal(CommandProvider::class, 'upsert')]
    public function testUpsert(array $firstData, array $secondData): void
    {
        parent::testUpsert($firstData, $secondData);
    }

    public function testUpsertWithReturningPks(): void
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Oracle\DMLQueryBuilder::upsertWithReturningPks is not supported by Oracle.');

        $command->upsertWithReturningPks('{{customer}}', ['name' => 'test_1', 'email' => 'test_1@example.com']);
    }

    public function testUpsertWithReturningPksEmptyValues()
    {
        $db = $this->getConnection();
        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Oracle\DMLQueryBuilder::upsertWithReturningPks is not supported by Oracle.');

        $command->upsertWithReturningPks('null_values', []);
    }

    public function testUpsertWithReturningPksWithPhpTypecasting(): void
    {
        $db = $this->getConnection();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Oracle\DMLQueryBuilder::upsertWithReturningPks is not supported by Oracle.');

        $db->createCommand()
            ->withPhpTypecasting()
            ->upsertWithReturningPks('notauto_pk', ['id_1' => 1, 'id_2' => 2.5, 'type' => 'test1']);
    }

    public function testQueryScalarWithBlob(): void
    {
        $db = $this->getConnection(true);

        $value = json_encode(['test'], JSON_THROW_ON_ERROR);
        $db->createCommand()->insert('{{%T_upsert_varbinary}}', ['id' => 1, 'blob_col' => $value])->execute();

        $scalarValue = $db->createCommand('SELECT [[blob_col]] FROM {{%T_upsert_varbinary}}')->queryScalar();
        $this->assertEquals($value, $scalarValue);
    }

    public function testProfiler(string|null $sql = null): void
    {
        parent::testProfiler('SELECT 123 FROM DUAL');
    }

    public function testProfilerData(string|null $sql = null): void
    {
        parent::testProfilerData('SELECT 123 FROM DUAL');
    }

    public function testShowDatabases(): void
    {
        $this->assertSame([self::getDatabaseName()], self::getDb()->createCommand()->showDatabases());
    }

    #[DataProviderExternal(CommandProvider::class, 'createIndex')]
    public function testCreateIndex(array $columns, array $indexColumns, string|null $indexType, string|null $indexMethod): void
    {
        parent::testCreateIndex($columns, $indexColumns, $indexType, $indexMethod);
    }

    public function testCreateSearchIndex()
    {
        $db = $this->getConnection();

        if (version_compare($db->getServerInfo()->getVersion(), '21', '<')) {
            $this->markTestSkipped('Search index is supported since Oracle 21');
        }

        $command = $db->createCommand();
        $schema = $db->getSchema();

        $tableName = 'test_create_index';
        $indexName = 'test_index_name';

        if ($schema->getTableSchema($tableName) !== null) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable($tableName, ['col1' => ColumnBuilder::text()])->execute();
        $command->createIndex($tableName, $indexName, ['col1'], IndexType::SEARCH)->execute();

        $this->assertCount(2, $schema->getTableIndexes($tableName));

        $index = $schema->getTableIndexes($tableName)[0];

        $this->assertSame(['col1'], $index->getColumnNames());
        $this->assertFalse($index->isUnique());
        $this->assertFalse($index->isPrimary());

        $sysIndex = $schema->getTableIndexes($tableName)[1];
        $this->assertSame([], $sysIndex->getColumnNames());
        $this->assertTrue($sysIndex->isUnique());
        $this->assertFalse($sysIndex->isPrimary());

        $db->close();
    }
}
