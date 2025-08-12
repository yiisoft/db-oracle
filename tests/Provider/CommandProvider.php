<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use JsonException;
use PDO;
use Yiisoft\Db\Expression\Param;
use Yiisoft\Db\Oracle\Column\ColumnBuilder;
use Yiisoft\Db\Oracle\IndexType;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Support\DbHelper;

use function array_merge;
use function json_encode;
use function serialize;

final class CommandProvider extends \Yiisoft\Db\Tests\Provider\CommandProvider
{
    use TestTrait;

    protected static string $driverName = 'oci';

    public static function batchInsert(): array
    {
        $batchInsert = parent::batchInsert();

        $replaceParams = [
            'multirow' => [
                ':qp1' => '1',
                ':qp2' => 'test string2',
                ':qp3' => '0',
            ],
            'issue11242' => [
                ':qp1' => '1',
            ],
            'table name with column name with brackets' => [
                ':qp1' => '0',
            ],
            'binds params from expression' => [
                ':qp2' => '0',
            ],
            'with associative values with different keys' => [
                ':qp1' => '1',
            ],
            'with associative values with different keys and columns with keys' => [
                ':qp1' => '1',
            ],
            'with associative values with keys of column names' => [
                ':qp0' => '1',
                ':qp1' => '10',
            ],
            'with associative values with keys of column keys' => [
                ':qp0' => '1',
                ':qp1' => '10',
            ],
            'with shuffled indexes of values' => [
                ':qp0' => '1',
                ':qp1' => '10',
            ],
            'empty columns and associative values' => [
                ':qp1' => '1',
            ],
            'empty columns and objects' => [
                ':qp1' => '1',
            ],
            'empty columns and a Traversable value' => [
                ':qp1' => '1',
            ],
            'empty columns and Traversable values' => [
                ':qp1' => '1',
            ],
            'binds json params' => [
                ':qp1' => '1',
                ':qp2' => '{"a":1,"b":true,"c":[1,2,3]}',
                ':qp3' => 'b',
                ':qp4' => '0',
                ':qp5' => '{"d":"e","f":false,"g":[4,5,null]}',
            ],
        ];

        foreach ($replaceParams as $key => $expectedParams) {
            DbHelper::changeSqlForOracleBatchInsert($batchInsert[$key]['expected'], $expectedParams);
            $batchInsert[$key]['expectedParams'] = array_merge($batchInsert[$key]['expectedParams'], $expectedParams);
        }

        $batchInsert['multirow']['expected'] = <<<SQL
            INSERT INTO "type" ("int_col", "float_col", "char_col", "bool_col")
            SELECT 0, 0, :qp0, :qp1 FROM DUAL UNION ALL
            SELECT 0, 0, :qp2, :qp3 FROM DUAL
            SQL;

        $batchInsert['with associative values with keys of column names']['expected'] = <<<SQL
            INSERT INTO "type" ("int_col", "float_col", "char_col", "bool_col")
            SELECT 1, 2, :qp1, :qp0 FROM DUAL
            SQL;

        $batchInsert['with associative values with keys of column keys']['expected'] = <<<SQL
            INSERT INTO "type" ("int_col", "float_col", "char_col", "bool_col")
            SELECT 1, 2, :qp1, :qp0 FROM DUAL
            SQL;

        $batchInsert['with shuffled indexes of values']['expected'] = <<<SQL
            INSERT INTO "type" ("int_col", "float_col", "char_col", "bool_col")
            SELECT 1, 2, :qp1, :qp0 FROM DUAL
            SQL;

        $batchInsert['binds json params']['expected'] = <<<SQL
            INSERT INTO "type" ("int_col", "char_col", "float_col", "bool_col", "json_col")
            SELECT 1, :qp0, 0, :qp1, :qp2 FROM DUAL UNION ALL
            SELECT 2, :qp3, -1, :qp4, :qp5 FROM DUAL
            SQL;

        return $batchInsert;
    }

    /**
     * @throws JsonException
     */
    public static function insertVarbinary(): array
    {
        return [
            [
                json_encode(['string' => 'string', 'integer' => 1234], JSON_THROW_ON_ERROR),
                json_encode(['string' => 'string', 'integer' => 1234], JSON_THROW_ON_ERROR),
            ],
            [
                serialize(['string' => 'string', 'integer' => 1234]),
                new Param(serialize(['string' => 'string', 'integer' => 1234]), PDO::PARAM_LOB),
            ],
            ['simple string', 'simple string'],
        ];
    }

    public static function rawSql(): array
    {
        $rawSql = parent::rawSql();

        foreach ($rawSql as &$values) {
            $values[2] = strtr($values[2], [
                'FALSE' => "'0'",
                'TRUE' => "'1'",
            ]);
        }

        return $rawSql;
    }

    public static function createIndex(): array
    {
        return [
            ...parent::createIndex(),
            [['col1' => ColumnBuilder::integer()], ['col1'], IndexType::UNIQUE, null],
            [['col1' => ColumnBuilder::integer()], ['col1'], IndexType::BITMAP, null],
        ];
    }

    public static function upsertReturning(): array
    {
        return [['table', [], true, ['col1'], [], []]];
    }
}
