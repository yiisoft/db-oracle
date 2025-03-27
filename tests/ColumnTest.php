<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Oracle\Column\BinaryColumn;
use Yiisoft\Db\Oracle\Column\JsonColumn;
use Yiisoft\Db\Oracle\Connection;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Tests\AbstractColumnTest;

use function str_repeat;
use function version_compare;

/**
 * @group oracle
 */
final class ColumnTest extends AbstractColumnTest
{
    use TestTrait;

    private function insertTypeValues(Connection $db): void
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
                'bool_col' => false,
                'bit_col' => 0b0110_0110, // 102
                'json_col' => [['a' => 1, 'b' => null, 'c' => [1, 3, 5]]],
            ]
        )->execute();
    }

    private function assertResultValues(array $result, string $varsion): void
    {
        $this->assertSame(1, $result['int_col']);
        $this->assertSame(str_repeat('x', 100), $result['char_col']);
        $this->assertNull($result['char_col3']);
        $this->assertSame(1.234, $result['float_col']);
        $this->assertSame("\x10\x11\x12", stream_get_contents($result['blob_col']));
        $this->assertEquals(false, $result['bool_col']);
        $this->assertSame(0b0110_0110, $result['bit_col']);

        if (version_compare($varsion, '21', '>=')) {
            $this->assertSame([['a' => 1, 'b' => null, 'c' => [1, 3, 5]]], $result['json_col']);
        } else {
            $this->assertSame('[{"a":1,"b":null,"c":[1,3,5]}]', stream_get_contents($result['json_col']));
        }
    }

    public function testQueryTypecasting(): void
    {
        $db = $this->getConnection();
        $varsion = $db->getServerInfo()->getVersion();
        $db->close();

        if (version_compare($varsion, '21', '>=')) {
            $this->fixture = 'oci21.sql';
        }

        $db = $this->getConnection(true);

        $this->insertTypeValues($db);

        $result = (new Query($db))->typecasting()->from('type')->one();

        $this->assertResultValues($result, $varsion);

        $db->close();
    }

    public function testCommandPhpTypecasting(): void
    {
        $db = $this->getConnection();
        $varsion = $db->getServerInfo()->getVersion();
        $db->close();

        if (version_compare($varsion, '21', '>=')) {
            $this->fixture = 'oci21.sql';
        }

        $db = $this->getConnection(true);

        $this->insertTypeValues($db);

        $result = $db->createCommand('SELECT * FROM "type"')->phpTypecasting()->queryOne();

        $this->assertResultValues($result, $varsion);

        $db->close();
    }

    public function testSelectPhpTypecasting(): void
    {
        $db = $this->getConnection();

        $sql = "SELECT null, 1, 2.5, 'string' FROM DUAL";

        $expected = [
            'NULL' => null,
            1 => 1.0,
            '2.5' => 2.5,
            "'STRING'" => 'string',
        ];

        $result = $db->createCommand($sql)->phpTypecasting()->queryOne();

        $this->assertSame($expected, $result);

        $result = $db->createCommand($sql)->phpTypecasting()->queryAll();

        $this->assertSame([$expected], $result);

        $result = $db->createCommand('SELECT 2.5 FROM DUAL')->phpTypecasting()->queryScalar();

        $this->assertSame(2.5, $result);

        $result = $db->createCommand('SELECT 2.5 FROM DUAL UNION SELECT 3.3 FROM DUAL')->phpTypecasting()->queryColumn();

        $this->assertSame([2.5, 3.3], $result);

        $db->close();
    }

    public function testPhpTypeCast(): void
    {
        $db = $this->getConnection();

        if (version_compare($db->getServerInfo()->getVersion(), '21', '>=')) {
            $this->fixture = 'oci21.sql';
        }

        $db->close();
        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->insertTypeValues($db);

        $query = (new Query($db))->from('type')->one();

        $intColPhpType = $tableSchema->getColumn('int_col')?->phpTypecast($query['int_col']);
        $charColPhpType = $tableSchema->getColumn('char_col')?->phpTypecast($query['char_col']);
        $charCol3PhpType = $tableSchema->getColumn('char_col3')?->phpTypecast($query['char_col3']);
        $floatColPhpType = $tableSchema->getColumn('float_col')?->phpTypecast($query['float_col']);
        $blobColPhpType = $tableSchema->getColumn('blob_col')?->phpTypecast($query['blob_col']);
        $boolColPhpType = $tableSchema->getColumn('bool_col')?->phpTypecast($query['bool_col']);
        $bitColPhpType = $tableSchema->getColumn('bit_col')?->phpTypecast($query['bit_col']);
        $jsonColPhpType = $tableSchema->getColumn('json_col')?->phpTypecast($query['json_col']);

        $this->assertSame(1, $intColPhpType);
        $this->assertSame(str_repeat('x', 100), $charColPhpType);
        $this->assertNull($charCol3PhpType);
        $this->assertSame(1.234, $floatColPhpType);
        $this->assertSame("\x10\x11\x12", stream_get_contents($blobColPhpType));
        $this->assertEquals(false, $boolColPhpType);
        $this->assertSame(0b0110_0110, $bitColPhpType);
        $this->assertSame([['a' => 1, 'b' => null, 'c' => [1, 3, 5]]], $jsonColPhpType);

        $db->close();
    }

    public function testColumnInstance(): void
    {
        $db = $this->getConnection();

        if (version_compare($db->getServerInfo()->getVersion(), '21', '>=')) {
            $this->fixture = 'oci21.sql';
        }

        $db->close();
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->assertInstanceOf(IntegerColumn::class, $tableSchema->getColumn('int_col'));
        $this->assertInstanceOf(StringColumn::class, $tableSchema->getColumn('char_col'));
        $this->assertInstanceOf(DoubleColumn::class, $tableSchema->getColumn('float_col'));
        $this->assertInstanceOf(BinaryColumn::class, $tableSchema->getColumn('blob_col'));
        $this->assertInstanceOf(JsonColumn::class, $tableSchema->getColumn('json_col'));
    }

    /** @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\ColumnProvider::predefinedTypes */
    public function testPredefinedType(string $className, string $type, string $phpType): void
    {
        parent::testPredefinedType($className, $type, $phpType);
    }

    /** @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\ColumnProvider::dbTypecastColumns */
    public function testDbTypecastColumns(ColumnInterface $column, array $values): void
    {
        parent::testDbTypecastColumns($column, $values);
    }

    public function testBinaryColumn(): void
    {
        $binaryCol = new BinaryColumn();
        $binaryCol->dbType('blob');

        $this->assertInstanceOf(Expression::class, $binaryCol->dbTypecast("\x10\x11\x12"));
        $this->assertInstanceOf(
            Expression::class,
            $binaryCol->dbTypecast(new Param("\x10\x11\x12", PDO::PARAM_LOB)),
        );
    }

    public function testJsonColumn(): void
    {
        $jsonCol = new JsonColumn();

        $this->assertNull($jsonCol->phpTypecast(null));
    }

    public function testUniqueColumn(): void
    {
        $db = $this->getConnection(true);
        $schema = $db->getSchema();

        $this->assertTrue($schema->getTableSchema('T_constraints_1')?->getColumn('C_unique')->isUnique());
        $this->assertFalse($schema->getTableSchema('T_constraints_2')?->getColumn('C_index_2_1')->isUnique());
        $this->assertFalse($schema->getTableSchema('T_constraints_2')?->getColumn('C_index_2_2')->isUnique());
        $this->assertTrue($schema->getTableSchema('T_upsert')?->getColumn('email')->isUnique());
        $this->assertFalse($schema->getTableSchema('T_upsert')?->getColumn('recovery_email')->isUnique());
    }
}
