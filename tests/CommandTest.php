<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PDO;
use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Oracle\Schema;
use Yiisoft\Db\TestUtility\TestCommandTrait;

/**
 * @group oracle
 */
final class CommandTest extends TestCase
{
    use TestCommandTrait;

    protected string $upsertTestCharCast = 'CAST([[address]] AS VARCHAR(255))';

    public function testAddDropPrimaryKey(): void
    {
        $db = $this->getConnection();

        $tableName = 'test_pk';
        $name = 'test_pk_constraint';

        $schema = $db->getSchema();

        if ($schema->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable($tableName, [
            'int1' => 'integer not null',
            'int2' => 'integer not null',
        ])->execute();

        $this->assertNull($schema->getTablePrimaryKey($tableName, true));

        $db->createCommand()->addPrimaryKey($name, $tableName, ['int1'])->execute();

        $this->assertEquals(['int1'], $schema->getTablePrimaryKey($tableName, true)->getColumnNames());

        $db->createCommand()->dropPrimaryKey($name, $tableName)->execute();

        $this->assertNull($schema->getTablePrimaryKey($tableName, true));

        $db->createCommand()->addPrimaryKey($name, $tableName, ['int1', 'int2'])->execute();

        $this->assertEquals(['int1', 'int2'], $schema->getTablePrimaryKey($tableName, true)->getColumnNames());
    }

    public function testAutoQuoting(): void
    {
        $db = $this->getConnection();

        $sql = 'SELECT [[id]], [[t.name]] FROM {{customer}} t';
        $command = $db->createCommand($sql);
        $this->assertEquals('SELECT "id", "t"."name" FROM "customer" t', $command->getSql());
    }

    public function testLastInsertId(): void
    {
        $db = $this->getConnection(true);

        $sql = 'INSERT INTO {{profile}}([[description]]) VALUES (\'non duplicate\')';

        $command = $db->createCommand($sql);

        $command->execute();

        $this->assertEquals(3, $db->getSchema()->getLastInsertID('profile_SEQ'));
    }

    public function testCLOBStringInsertion(): void
    {
        $db = $this->getConnection();

        if ($db->getSchema()->getTableSchema('longstring') !== null) {
            $db->createCommand()->dropTable('longstring')->execute();
        }

        $db->createCommand()->createTable('longstring', ['message' => Schema::TYPE_TEXT])->execute();

        $longData = str_pad('-', 4001, '-=', STR_PAD_LEFT);
        $db->createCommand()->insert('longstring', [
            'message' => $longData,
        ])->execute();

        $this->assertEquals(1, $db->createCommand('SELECT count(*) FROM {{longstring}}')->queryScalar());

        $db->createCommand()->dropTable('longstring')->execute();
    }

    /**
     * Test batch insert with different data types.
     *
     * Ensure double is inserted with `.` decimal separator.
     *
     * {@see https://github.com/yiisoft/yii2/issues/6526}
     */
    public function testBatchInsertDataTypesLocale(): void
    {
        $locale = setlocale(LC_NUMERIC, 0);

        if (false === $locale) {
            $this->markTestSkipped('Your platform does not support locales.');
        }

        $db = $this->getConnection(true);

        try {
            /* This one sets decimal mark to comma sign */
            setlocale(LC_NUMERIC, 'ru_RU.utf8');

            $cols = ['int_col', 'char_col', 'float_col', 'bool_col'];

            $data = [
                [1, 'A', 9.735, true],
                [2, 'B', -2.123, false],
                [3, 'C', 2.123, false],
            ];

            /* clear data in "type" table */
            $db->createCommand()->delete('type')->execute();

            /* batch insert on "type" table */
            $db->createCommand()->batchInsert('type', $cols, $data)->execute();

            $data = $db->createCommand(
                'SELECT [[int_col]], [[char_col]], [[float_col]], [[bool_col]] FROM {{type}} WHERE [[int_col]] IN (1,2,3) ORDER BY [[int_col]]'
            )->queryAll();

            $this->assertCount(3, $data);
            $this->assertEquals(1, $data[0]['int_col']);
            $this->assertEquals(2, $data[1]['int_col']);
            $this->assertEquals(3, $data[2]['int_col']);

            /* rtrim because Postgres padds the column with whitespace */
            $this->assertEquals('A', rtrim($data[0]['char_col']));
            $this->assertEquals('B', rtrim($data[1]['char_col']));
            $this->assertEquals('C', rtrim($data[2]['char_col']));
            $this->assertEquals('9,735', $data[0]['float_col']);
            $this->assertEquals('-2,123', $data[1]['float_col']);
            $this->assertEquals('2,123', $data[2]['float_col']);
            $this->assertEquals('1', $data[0]['bool_col']);
            $this->assertIsOneOf($data[1]['bool_col'], ['0', false]);
            $this->assertIsOneOf($data[2]['bool_col'], ['0', false]);
        } catch (Exception $e) {
            setlocale(LC_NUMERIC, $locale);

            throw $e;
        } catch (Throwable $e) {
            setlocale(LC_NUMERIC, $locale);

            throw $e;
        }

        setlocale(LC_NUMERIC, $locale);
    }

    public function testInsert(): void
    {
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{customer}}')->execute();

        $command = $db->createCommand();

        $command->insert(
            '{{customer}}',
            [
                'email'   => 't1@example.com',
                'name'    => 'test',
                'address' => 'test address',
            ]
        )->execute();

        $this->assertEquals(1, $db->createCommand('SELECT COUNT(*) FROM {{customer}}')->queryScalar());

        $record = $db->createCommand('SELECT [[email]], [[name]], [[address]] FROM {{customer}}')->queryOne();

        $this->assertEquals([
            'email'   => 't1@example.com',
            'name'    => 'test',
            'address' => 'test address',
        ], $record);
    }

    public function testQueryCache()
    {
        $db = $this->getConnection(true);

        $db->setEnableQueryCache(true);
        $db->setQueryCache($this->cache);

        $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id');

        $this->assertEquals('user1', $command->bindValue(':id', 1)->queryScalar());

        $update = $db->createCommand('UPDATE {{customer}} SET [[name]] = :name WHERE [[id]] = :id');
        $update->bindValues([':id' => 1, ':name' => 'user11'])->execute();

        $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id');

        $this->assertEquals('user11', $command->bindValue(':id', 1)->queryScalar());

        $db->cache(function (Connection $db) use ($update) {
            $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id');

            $this->assertEquals('user2', $command->bindValue(':id', 2)->queryScalar());

            $update->bindValues([':id' => 2, ':name' => 'user22'])->execute();

            $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id');

            $this->assertEquals('user2', $command->bindValue(':id', 2)->queryScalar());

            $db->noCache(function () use ($db) {
                $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id');

                $this->assertEquals('user22', $command->bindValue(':id', 2)->queryScalar());
            });

            $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id');

            $this->assertEquals('user2', $command->bindValue(':id', 2)->queryScalar());
        }, 10);

        $db->setEnableQueryCache(false);

        $db->cache(function (Connection $db) use ($update) {
            $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id');

            $this->assertEquals('user22', $command->bindValue(':id', 2)->queryScalar());

            $update->bindValues([':id' => 2, ':name' => 'user2'])->execute();

            $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id');

            $this->assertEquals('user2', $command->bindValue(':id', 2)->queryScalar());
        }, 10);

        $db->setEnableQueryCache(true);

        $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id')->cache();

        $this->assertEquals('user11', $command->bindValue(':id', 1)->queryScalar());

        $update->bindValues([':id' => 1, ':name' => 'user1'])->execute();

        $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id')->cache();

        $this->assertEquals('user11', $command->bindValue(':id', 1)->queryScalar());

        $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id')->noCache();

        $this->assertEquals('user1', $command->bindValue(':id', 1)->queryScalar());

        $db->cache(function (Connection $db) use ($update) {
            $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id');

            $this->assertEquals('user11', $command->bindValue(':id', 1)->queryScalar());

            $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id')->noCache();

            $this->assertEquals('user1', $command->bindValue(':id', 1)->queryScalar());
        }, 10);
    }

    /**
     * verify that {{}} are not going to be replaced in parameters.
     */
    public function testNoTablenameReplacement(): void
    {
        $db = $this->getConnection(true);

        $db->createCommand()->insert(
            '{{customer}}',
            [
                'name'    => 'Some {{weird}} name',
                'email'   => 'test@example.com',
                'address' => 'Some {{%weird}} address',
            ]
        )->execute();

        $customerId = $db->getLastInsertID('customer_SEQ');

        $customer = $db->createCommand('SELECT * FROM {{customer}} WHERE "id"=' . $customerId)->queryOne();

        $this->assertEquals('Some {{weird}} name', $customer['name']);
        $this->assertEquals('Some {{%weird}} address', $customer['address']);

        $db->createCommand()->update(
            '{{customer}}',
            [
                'name'    => 'Some {{updated}} name',
                'address' => 'Some {{%updated}} address',
            ],
            ['id' => $customerId]
        )->execute();

        $customer = $db->createCommand('SELECT * FROM {{customer}} WHERE "id"=' . $customerId)->queryOne();

        $this->assertEquals('Some {{updated}} name', $customer['name']);
        $this->assertEquals('Some {{%updated}} address', $customer['address']);
    }

    public function testBindParamValue(): void
    {
        $this->markTestSkipped('Should be fixed');
        $db = $this->getConnection(true);

        /** bindParam */
        $sql = 'INSERT INTO {{customer}}([[email]], [[name]], [[address]]) VALUES (:email, :name, :address)';
        $command = $db->createCommand($sql);
        $email = 'user4@example.com';
        $name = 'user4';
        $address = 'address4';
        $command->bindParam(':email', $email);
        $command->bindParam(':name', $name);
        $command->bindParam(':address', $address);
        $command->execute();

        $sql = 'SELECT [[name]] FROM {{customer}} WHERE [[email]] = :email';
        $command = $db->createCommand($sql);
        $command->bindParam(':email', $email);
        $this->assertEquals($name, $command->queryScalar());

        $sql = <<<'SQL'
INSERT INTO {{type}} ([[int_col]], [[char_col]], [[float_col]], [[blob_col]], [[numeric_col]], [[bool_col]])
  VALUES (:int_col, :char_col, :float_col, :blob_col, :numeric_col, :bool_col)
SQL;
        $command = $db->createCommand($sql);
        $intCol = 123;
        $charCol = str_repeat('abc', 33) . 'x'; // a 100 char string
        $boolCol = false;
        $command->bindParam(':int_col', $intCol, PDO::PARAM_INT);
        $command->bindParam(':char_col', $charCol);
        $command->bindParam(':bool_col', $boolCol, PDO::PARAM_BOOL);

        /** can't bind floats without support from a custom PDO driver */
        $floatCol = 2;
        $numericCol = 3;
        /** can't use blobs without support from a custom PDO driver */
        $blobCol = null;
        $command->bindParam(':float_col', $floatCol, PDO::PARAM_INT);
        $command->bindParam(':numeric_col', $numericCol, PDO::PARAM_INT);
        $command->bindParam(':blob_col', $blobCol);

        $this->assertEquals(1, $command->execute());

        $command = $db->createCommand(
            'SELECT [[int_col]], [[char_col]], [[float_col]], [[blob_col]], [[numeric_col]], [[bool_col]] FROM {{type}}'
        );
        $row = $command->queryOne();
        $this->assertEquals($intCol, $row['int_col']);
        $this->assertEquals($charCol, $row['char_col']);
        $this->assertEquals($floatCol, $row['float_col']);
        $this->assertEquals($blobCol, $row['blob_col']);
        $this->assertEquals($numericCol, $row['numeric_col']);
        $this->assertEquals($boolCol, (int)$row['bool_col']);

        /** bindValue */
        $sql = 'INSERT INTO {{customer}}([[email]], [[name]], [[address]]) VALUES (:email, \'user5\', \'address5\')';
        $command = $db->createCommand($sql);
        $command->bindValue(':email', 'user5@example.com');
        $command->execute();

        $sql = 'SELECT [[email]] FROM {{customer}} WHERE [[name]] = :name';
        $command = $db->createCommand($sql);
        $command->bindValue(':name', 'user5');
        $this->assertEquals('user5@example.com', $command->queryScalar());
    }

    public function bindParamsNonWhereProvider(): array
    {
        return [
            ['SELECT SUBSTR("name", :len) FROM {{customer}} WHERE [[email]] = :email GROUP BY SUBSTR("name", :len)'],
            ['SELECT SUBSTR("name", :len) FROM {{customer}} WHERE [[email]] = :email ORDER BY SUBSTR("name", :len)'],
            ['SELECT SUBSTR("name", :len) FROM {{customer}} WHERE [[email]] = :email']
        ];
    }

    /**
     * Test whether param binding works in other places than WHERE.
     *
     * @dataProvider bindParamsNonWhereProvider
     *
     * @param string $sql
     */
    public function testBindParamsNonWhere(string $sql): void
    {
        $db = $this->getConnection(true);

        $db->createCommand()->insert(
            '{{customer}}',
            ['name' => 'testParams', 'email' => 'testParams@example.com', 'address' => '1']
        )->execute();

        $params = [
            ':email' => 'testParams@example.com',
            ':len' => 5,
        ];

        $command = $db->createCommand($sql, $params);

        $this->assertEquals('Params', $command->queryScalar());
    }

    /**
     * Data provider for testInsertSelectFailed.
     *
     * @return array
     */
    public function invalidSelectColumns()
    {
        return [
            [[]],
            ['*'],
            [['*']],
        ];
    }

    /**
     * Test INSERT INTO ... SELECT SQL statement with wrong query object.
     *
     * @dataProvider invalidSelectColumns
     * @param mixed $invalidSelectColumns
     */
    public function testInsertSelectFailed($invalidSelectColumns): void
    {
        $db = $this->getConnection();
        $query = new Query($db);

        $query->select($invalidSelectColumns)->from('{{customer}}');

        $command = $db->createCommand();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('select query object with enumerated (named) parameters');

        $command->insert(
            '{{customer}}',
            $query
        )->execute();
    }

    /**
     * @dataProvider upsertProviderTrait
     *
     * @param array $firstData
     * @param array $secondData
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        $db = $this->getConnection(true);

        $this->assertEquals(0, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());
        $this->performAndCompareUpsertResult($db, $firstData);
        $this->assertEquals(1, $db->createCommand('SELECT COUNT(*) FROM {{T_upsert}}')->queryScalar());
        $this->performAndCompareUpsertResult($db, $secondData);
    }

    public function testsInsertQueryAsColumnValue(): void
    {
        $db = $this->getConnection(true);

        $time = time();

        $db->createCommand('DELETE FROM {{order_with_null_fk}}')->execute();

        $command = $db->createCommand();

        $command->insert('{{order}}', [
            'customer_id' => 1,
            'created_at'  => $time,
            'total'       => 42,
        ])->execute();

        $columnValueQuery = new Query($db);

        $orderId = $db->getLastInsertID('order_SEQ');

        $columnValueQuery->select('created_at')->from('{{order}}')->where(['id' => $orderId]);

        $command = $db->createCommand();

        $command->insert(
            '{{order_with_null_fk}}',
            [
                'customer_id' => $orderId,
                'created_at'  => $columnValueQuery,
                'total'       => 42,
            ]
        )->execute();

        $this->assertEquals(
            $time,
            $db->createCommand(
                'SELECT [[created_at]] FROM {{order_with_null_fk}} WHERE [[customer_id]] = ' . $orderId
            )->queryScalar()
        );

        $db->createCommand('DELETE FROM {{order_with_null_fk}}')->execute();
        $db->createCommand('DELETE FROM {{order}} WHERE [[id]] = ' . $orderId)->execute();
    }

    /**
     * Test INSERT INTO ... SELECT SQL statement with alias syntax.
     */
    public function testInsertSelectAlias(): void
    {
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{customer}}')->execute();

        $command = $db->createCommand();

        $command->insert(
            '{{customer}}',
            [
                'email'   => 't1@example.com',
                'name'    => 'test',
                'address' => 'test address',
            ]
        )->execute();

        $query = $db->createCommand(
            "SELECT 't2@example.com' as [[email]], [[address]] as [[name]], [[name]] as [[address]] from {{customer}}"
        );

        $command->insert(
            '{{customer}}',
            $query->queryOne()
        )->execute();

        $this->assertEquals(2, $db->createCommand('SELECT COUNT(*) FROM {{customer}}')->queryScalar());

        $record = $db->createCommand('SELECT [[email]], [[name]], [[address]] FROM {{customer}}')->queryAll();

        $this->assertEquals([
            [
                'email'   => 't1@example.com',
                'name'    => 'test',
                'address' => 'test address',
            ],
            [
                'email'   => 't2@example.com',
                'name'    => 'test address',
                'address' => 'test',
            ],
        ], $record);
    }

    public function testCreateTable(): void
    {
        $db = $this->getConnection(true);

        if ($db->getSchema()->getTableSchema("testCreateTable") !== null) {
            $db->createCommand("DROP SEQUENCE testCreateTable_SEQ")->execute();
            $db->createCommand()->dropTable("testCreateTable")->execute();
        }

        $db->createCommand()->createTable(
            '{{testCreateTable}}',
            ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER]
        )->execute();

        $db->createCommand('CREATE SEQUENCE testCreateTable_SEQ START with 1 INCREMENT BY 1')->execute();

        $db->createCommand(
            'INSERT INTO {{testCreateTable}} ("id", "bar") VALUES(testCreateTable_SEQ.NEXTVAL, 1)'
        )->execute();

        $records = $db->createCommand('SELECT [[id]], [[bar]] FROM {{testCreateTable}}')->queryAll();

        $this->assertEquals([
            ['id' => 1, 'bar' => 1],
        ], $records);
    }

    public function testCreateView(): void
    {
        $db = $this->getConnection();

        $subquery = (new Query($db))
            ->select('bar')
            ->from('testCreateViewTable')
            ->where(['>', 'bar', '5']);

        if ($db->getSchema()->getTableSchema('testCreateView') !== null) {
            $db->createCommand()->dropView('testCreateView')->execute();
        }

        if ($db->getSchema()->getTableSchema('testCreateViewTable')) {
            $db->createCommand("DROP SEQUENCE testCreateViewTable_SEQ")->execute();
            $db->createCommand()->dropTable('testCreateViewTable')->execute();
        }

        $db->createCommand()->createTable('testCreateViewTable', [
            'id'  => Schema::TYPE_PK,
            'bar' => Schema::TYPE_INTEGER,
        ])->execute();

        $db->createCommand('CREATE SEQUENCE testCreateViewTable_SEQ START with 1 INCREMENT BY 1')->execute();

        $db->createCommand(
            'INSERT INTO {{testCreateViewTable}} ("id", "bar") VALUES(testCreateTable_SEQ.NEXTVAL, 1)'
        )->execute();

        $db->createCommand(
            'INSERT INTO {{testCreateViewTable}} ("id", "bar") VALUES(testCreateTable_SEQ.NEXTVAL, 6)'
        )->execute();

        $db->createCommand()->createView('testCreateView', $subquery)->execute();

        $records = $db->createCommand('SELECT [[bar]] FROM {{testCreateView}}')->queryAll();

        $this->assertEquals([['bar' => 6]], $records);
    }

    public function testColumnCase(): void
    {
        $this->markTestSkipped('should be fixed.');

        $db = $this->getConnection(true);

        $this->assertEquals(PDO::CASE_NATURAL, $db->getSlavePdo()->getAttribute(PDO::ATTR_CASE));

        $sql = 'SELECT [[customer_id]], [[total]] FROM {{order}}';

        $rows = $db->createCommand($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['customer_id']));
        $this->assertTrue(isset($rows[0]['total']));

        $db->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $rows = $db->createCommand($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['customer_id']));
        $this->assertTrue(isset($rows[0]['total']));

        $db->getPDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $rows = $db->createCommand($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['CUSTOMER_ID']));
        $this->assertTrue(isset($rows[0]['TOTAL']));
    }

    public function testInsertExpression(): void
    {
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{order_with_null_fk}}')->execute();

        $command = $db->createCommand();

        $command->insert(
            '{{order_with_null_fk}}',
            [
                'created_at' => new Expression('EXTRACT(YEAR FROM sysdate)'),
                'total' => 1,
            ]
        )->execute();

        $this->assertEquals(1, $db->createCommand('SELECT COUNT(*) FROM {{order_with_null_fk}}')->queryScalar());

        $record = $db->createCommand('SELECT [[created_at]] FROM {{order_with_null_fk}}')->queryOne();

        $this->assertEquals([
            'created_at' => date('Y'),
        ], $record);
    }
}
