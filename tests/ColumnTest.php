<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Oracle\Column\BinaryColumn;
use Yiisoft\Db\Oracle\Column\ColumnBuilder;
use Yiisoft\Db\Oracle\Column\JsonColumn;
use Yiisoft\Db\Oracle\Tests\Provider\ColumnProvider;
use Yiisoft\Db\Oracle\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Oracle\Tests\Support\TestConnection;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Schema\Data\StringableStream;
use Yiisoft\Db\Tests\Common\CommonColumnTest;

use function iterator_to_array;
use function str_repeat;
use function version_compare;

/**
 * @group oracle
 */
final class ColumnTest extends CommonColumnTest
{
    use IntegrationTestTrait;

    public function testQueryWithTypecasting(): void
    {
        $db = $this->getSharedConnection();
        $isOldVersion = version_compare(TestConnection::getServerVersion(), '21', '<');

        $this->loadFixture(
            __DIR__ . '/Support/Fixture/'
            . ($isOldVersion ? 'oci.sql' : 'oci21.sql'),
        );

        $this->insertTypeValues($db);

        $query = (new Query($db))->from('type')->withTypecasting();

        $result = $query->one();

        $this->assertTypecastedValues($result, !$isOldVersion);

        $result = $query->all();

        $this->assertTypecastedValues($result[0], !$isOldVersion);

        $db->close();
    }

    public function testCommandWithPhpTypecasting(): void
    {
        $db = $this->getSharedConnection();
        $isOldVersion = version_compare(TestConnection::getServerVersion(), '21', '<');

        $this->loadFixture(
            __DIR__ . '/Support/Fixture/'
            . ($isOldVersion ? 'oci.sql' : 'oci21.sql'),
        );

        $this->insertTypeValues($db);

        $command = $db->createCommand('SELECT * FROM "type"');

        $result = $command->withPhpTypecasting()->queryOne();

        $this->assertTypecastedValues($result, !$isOldVersion);

        $result = $command->withPhpTypecasting()->queryAll();

        $this->assertTypecastedValues($result[0], !$isOldVersion);

        $db->close();
    }

    public function testSelectWithPhpTypecasting(): void
    {
        $db = $this->getSharedConnection();

        $sql = "SELECT null, 1, 2.5, 'string' FROM DUAL";

        $expected = [
            'NULL' => null,
            1 => 1.0,
            '2.5' => 2.5,
            "'STRING'" => 'string',
        ];

        $result = $db->createCommand($sql)
            ->withPhpTypecasting()
            ->queryOne();

        $this->assertSame($expected, $result);

        $result = $db->createCommand($sql)
            ->withPhpTypecasting()
            ->queryAll();

        $this->assertSame([$expected], $result);

        $result = $db->createCommand($sql)
            ->withPhpTypecasting()
            ->query();

        $this->assertSame([$expected], iterator_to_array($result));

        $result = $db->createCommand('SELECT 2.5 FROM DUAL')
            ->withPhpTypecasting()
            ->queryScalar();

        $this->assertSame(2.5, $result);

        $result = $db->createCommand('SELECT 2.5 FROM DUAL UNION SELECT 3.3 FROM DUAL')
            ->withPhpTypecasting()
            ->queryColumn();

        $this->assertSame([2.5, 3.3], $result);

        $db->close();
    }

    public function testPhpTypecast(): void
    {
        $db = $this->getSharedConnection();
        $isOldVersion = version_compare(TestConnection::getServerVersion(), '21', '<');

        $this->loadFixture(
            __DIR__ . '/Support/Fixture/'
            . ($isOldVersion ? 'oci.sql' : 'oci21.sql'),
        );

        parent::testPhpTypecast();
    }

    public function testColumnInstance(): void
    {
        $db = $this->getSharedConnection();
        $isOldVersion = version_compare(TestConnection::getServerVersion(), '21', '<');

        $this->loadFixture(
            __DIR__ . '/Support/Fixture/'
            . ($isOldVersion ? 'oci.sql' : 'oci21.sql'),
        );

        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->assertInstanceOf(IntegerColumn::class, $tableSchema->getColumn('int_col'));
        $this->assertInstanceOf(StringColumn::class, $tableSchema->getColumn('char_col'));
        $this->assertInstanceOf(DoubleColumn::class, $tableSchema->getColumn('float_col'));
        $this->assertInstanceOf(BinaryColumn::class, $tableSchema->getColumn('blob_col'));
        $this->assertInstanceOf(JsonColumn::class, $tableSchema->getColumn('json_col'));
    }

    #[DataProviderExternal(ColumnProvider::class, 'predefinedTypes')]
    public function testPredefinedType(string $className, string $type)
    {
        parent::testPredefinedType($className, $type);
    }

    #[DataProviderExternal(ColumnProvider::class, 'dbTypecastColumns')]
    public function testDbTypecastColumns(ColumnInterface $column, array $values)
    {
        parent::testDbTypecastColumns($column, $values);
    }

    #[DataProviderExternal(ColumnProvider::class, 'phpTypecastColumns')]
    public function testPhpTypecastColumns(ColumnInterface $column, array $values)
    {
        parent::testPhpTypecastColumns($column, $values);
    }

    public function testBinaryColumn(): void
    {
        $binaryCol = new BinaryColumn();
        $binaryCol->dbType('blob');

        $expected = new Expression('TO_BLOB(UTL_RAW.CAST_TO_RAW(:value))', ['value' => "\x10\x11\x12"]);

        $this->assertEquals(
            $expected,
            $binaryCol->dbTypecast("\x10\x11\x12"),
        );
        $this->assertEquals(
            $expected,
            $binaryCol->dbTypecast(new Param("\x10\x11\x12", PDO::PARAM_LOB)),
        );
        $this->assertEquals(
            $expected,
            $binaryCol->dbTypecast(new StringableStream("\x10\x11\x12")),
        );
    }

    public function testJsonColumn(): void
    {
        $jsonCol = new JsonColumn();

        $this->assertNull($jsonCol->phpTypecast(null));
    }

    public function testUniqueColumn(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $schema = $db->getSchema();

        $this->assertTrue($schema->getTableSchema('T_constraints_1')?->getColumn('C_unique')->isUnique());
        $this->assertFalse($schema->getTableSchema('T_constraints_2')?->getColumn('C_index_2_1')->isUnique());
        $this->assertFalse($schema->getTableSchema('T_constraints_2')?->getColumn('C_index_2_2')->isUnique());
        $this->assertTrue($schema->getTableSchema('T_upsert')?->getColumn('email')->isUnique());
        $this->assertFalse($schema->getTableSchema('T_upsert')?->getColumn('recovery_email')->isUnique());
    }

    public function testTimestampColumnOnDifferentTimezones(): void
    {
        $db = $this->createConnection();
        $schema = $db->getSchema();
        $command = $db->createCommand();
        $tableName = 'timestamp_column_test';

        $command->setSql("ALTER SESSION SET TIME_ZONE = '+03:00'")->execute();

        $this->assertSame('+03:00', $db->getServerInfo()->getTimezone());

        $phpTimezone = date_default_timezone_get();
        date_default_timezone_set('America/New_York');

        if ($schema->hasTable($tableName)) {
            $command->dropTable($tableName)->execute();
        }

        $command->createTable(
            $tableName,
            [
                'timestamp_col' => ColumnBuilder::timestamp(),
                'datetime_col' => ColumnBuilder::datetime(),
            ],
        )->execute();

        $command->insert($tableName, [
            'timestamp_col' => new DateTimeImmutable('2025-04-19 14:11:35'),
            'datetime_col' => new DateTimeImmutable('2025-04-19 14:11:35'),
        ])->execute();

        $command->setSql("ALTER SESSION SET TIME_ZONE = '+04:00'")->execute();

        $this->assertSame('+04:00', $db->getServerInfo()->getTimezone(true));

        $columns = $schema->getTableSchema($tableName, true)->getColumns();
        $query = (new Query($db))->from($tableName);

        $result = $query->one();

        $this->assertEquals(new DateTimeImmutable('2025-04-19 14:11:35'), $columns['timestamp_col']->phpTypecast($result['timestamp_col']));
        $this->assertEquals(new DateTimeImmutable('2025-04-19 14:11:35'), $columns['datetime_col']->phpTypecast($result['datetime_col']));

        $result = $query->withTypecasting()->one();

        $this->assertEquals(new DateTimeImmutable('2025-04-19 14:11:35'), $result['timestamp_col']);
        $this->assertEquals(new DateTimeImmutable('2025-04-19 14:11:35'), $result['datetime_col']);

        date_default_timezone_set($phpTimezone);

        $db->close();
    }

    protected function insertTypeValues(ConnectionInterface $db): void
    {
        $db->createCommand()->insert(
            'type',
            [
                'int_col' => 1,
                'char_col' => str_repeat('x', 100),
                'char_col3' => null,
                'float_col' => 1.234,
                'blob_col' => "\x10\x11\x12",
                'timestamp_col' => new Expression("TIMESTAMP '2023-07-11 14:50:23'"),
                'timestamp_local' => '2023-07-11 14:50:23',
                'time_col' => new DateTimeImmutable('14:50:23'),
                'bool_col' => false,
                'bit_col' => 0b0110_0110, // 102
                'json_col' => [['a' => 1, 'b' => null, 'c' => [1, 3, 5]]],
            ],
        )->execute();
    }

    protected function assertTypecastedValues(array $result, bool $allTypecasted = false): void
    {
        $utcTimezone = new DateTimeZone('UTC');

        $this->assertSame(1, $result['int_col']);
        $this->assertSame(str_repeat('x', 100), $result['char_col']);
        $this->assertNull($result['char_col3']);
        $this->assertSame(1.234, $result['float_col']);
        $this->assertSame("\x10\x11\x12", (string) $result['blob_col']);
        $this->assertEquals(new DateTimeImmutable('2023-07-11 14:50:23', $utcTimezone), $result['timestamp_col']);
        $this->assertEquals(new DateTimeImmutable('2023-07-11 14:50:23', $utcTimezone), $result['timestamp_local']);
        $this->assertEquals(new DateTimeImmutable('14:50:23'), $result['time_col']);
        $this->assertEquals(false, $result['bool_col']);
        $this->assertSame(0b0110_0110, $result['bit_col']);

        if ($allTypecasted) {
            $this->assertSame([['a' => 1, 'b' => null, 'c' => [1, 3, 5]]], $result['json_col']);
        } else {
            $this->assertSame('[{"a":1,"b":null,"c":[1,3,5]}]', (string) $result['json_col']);
        }
    }
}
