<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Oracle\Column\BinaryColumn;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
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

    public function testPhpTypeCast(): void
    {
        if (version_compare($this->getConnection()->getServerInfo()->getVersion(), '21', '>=')) {
            $this->fixture = 'oci21.sql';
        }

        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $command->insert('type', [
            'int_col' => 1,
            'char_col' => str_repeat('x', 100),
            'char_col3' => null,
            'float_col' => 1.234,
            'blob_col' => "\x10\x11\x12",
            'timestamp_col' => new Expression("TIMESTAMP '2023-07-11 14:50:23'"),
            'bool_col' => false,
            'bit_col' => 0b0110_0110, // 102
            'json_col' => [['a' => 1, 'b' => null, 'c' => [1, 3, 5]]],
        ]);
        $command->execute();
        $query = (new Query($db))->from('type')->one();

        $this->assertNotNull($tableSchema);

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
        if (version_compare($this->getConnection()->getServerInfo()->getVersion(), '21', '>=')) {
            $this->fixture = 'oci21.sql';
        }

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
