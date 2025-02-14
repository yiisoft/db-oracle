<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use JsonException;
use PDO;
use Yiisoft\Db\Command\Param;
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
                ':qp3' => '1',
                ':qp7' => '0',
            ],
            'issue11242' => [
                ':qp3' => '1',
            ],
            'table name with column name with brackets' => [
                ':qp3' => '0',
            ],
            'binds params from expression' => [
                ':qp3' => '0',
            ],
            'with associative values with different keys' => [
                ':qp3' => '1',
            ],
            'with associative values with different keys and columns with keys' => [
                ':qp3' => '1',
            ],
            'with associative values with keys of column names' => [
                ':qp0' => '1',
            ],
            'with associative values with keys of column keys' => [
                ':qp0' => '1',
            ],
            'with shuffled indexes of values' => [
                ':qp0' => '1',
            ],
            'empty columns and associative values' => [
                ':qp3' => '1',
            ],
            'empty columns and objects' => [
                ':qp3' => '1',
            ],
            'empty columns and a Traversable value' => [
                ':qp3' => '1',
            ],
            'empty columns and Traversable values' => [
                ':qp3' => '1',
            ],
        ];

        foreach ($replaceParams as $key => $expectedParams) {
            DbHelper::changeSqlForOracleBatchInsert($batchInsert[$key]['expected']);
            $batchInsert[$key]['expectedParams'] = array_merge($batchInsert[$key]['expectedParams'], $expectedParams);
        }

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

    public static function createIndex(): array
    {
        return [
            ...parent::createIndex(),
            [['col1' => ColumnBuilder::integer()], ['col1'], IndexType::UNIQUE, null],
            [['col1' => ColumnBuilder::integer()], ['col1'], IndexType::BITMAP, null],
        ];
    }
}
