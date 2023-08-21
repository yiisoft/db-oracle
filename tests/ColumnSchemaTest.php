<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;

use function str_repeat;

/**
 * @group oracle
 */
final class ColumnSchemaTest extends TestCase
{
    use TestTrait;

    public function testPhpTypeCast(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $command->insert(
            'type',
            [
                'int_col' => 1,
                'char_col' => str_repeat('x', 100),
                'char_col3' => null,
                'float_col' => 1.234,
                'blob_col' => "\x10\x11\x12",
                'timestamp_col' => '2023-07-11 14:50:23',
                'timestamp_col2' => new DateTimeImmutable('2023-07-11 14:50:23.123456 +02:00'),
                'timestamptz_col' => new DateTimeImmutable('2023-07-11 14:50:23.12 -2:30'),
                'date_col' => new DateTimeImmutable('2023-07-11'),
                'bool_col' => false,
                'bit_col' => 0b0110_0110, // 102
            ]
        );
        $command->execute();
        $query = (new Query($db))->from('type')->one();

        $this->assertNotNull($tableSchema);

        $intColPhpType = $tableSchema->getColumn('int_col')?->phpTypecast($query['int_col']);
        $charColPhpType = $tableSchema->getColumn('char_col')?->phpTypecast($query['char_col']);
        $charCol3PhpType = $tableSchema->getColumn('char_col3')?->phpTypecast($query['char_col3']);
        $floatColPhpType = $tableSchema->getColumn('float_col')?->phpTypecast($query['float_col']);
        $blobColPhpType = $tableSchema->getColumn('blob_col')?->phpTypecast($query['blob_col']);
        $timestampColPhpType = $tableSchema->getColumn('timestamp_col')?->phpTypecast($query['timestamp_col']);
        $timestampCol2PhpType = $tableSchema->getColumn('timestamp_col2')?->phpTypecast($query['timestamp_col2']);
        $timestamptzColPhpType = $tableSchema->getColumn('timestamptz_col')?->phpTypecast($query['timestamptz_col']);
        $dateColPhpType = $tableSchema->getColumn('date_col')?->phpTypecast($query['date_col']);
        $tsDefaultPhpType = $tableSchema->getColumn('ts_default')?->phpTypecast($query['ts_default']);
        $boolColPhpType = $tableSchema->getColumn('bool_col')?->phpTypecast($query['bool_col']);
        $bitColPhpType = $tableSchema->getColumn('bit_col')?->phpTypecast($query['bit_col']);

        $this->assertSame(1, $intColPhpType);
        $this->assertSame(str_repeat('x', 100), $charColPhpType);
        $this->assertNull($charCol3PhpType);
        $this->assertSame(1.234, $floatColPhpType);
        $this->assertSame("\x10\x11\x12", stream_get_contents($blobColPhpType));
        $this->assertEquals(new DateTimeImmutable('2023-07-11 14:50:23'), $timestampColPhpType);
        $this->assertEquals(new DateTimeImmutable('2023-07-11 14:50:23.123456 +02:00'), $timestampCol2PhpType);
        $this->assertEquals(new DateTimeImmutable('2023-07-11 14:50:23.12 -2:30'), $timestamptzColPhpType);
        $this->assertEquals(new DateTimeImmutable('2023-07-11'), $dateColPhpType);
        $this->assertInstanceOf(DateTimeImmutable::class, $tsDefaultPhpType);
        $this->assertEquals(false, $boolColPhpType);
        $this->assertEquals(0b0110_0110, $bitColPhpType);

        $db->close();
    }
}
