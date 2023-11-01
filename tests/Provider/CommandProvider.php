<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use JsonException;
use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Support\DbHelper;

use function json_encode;
use function serialize;

final class CommandProvider extends \Yiisoft\Db\Tests\Provider\CommandProvider
{
    use TestTrait;

    protected static string $driverName = 'oci';

    public static function batchInsert(): array
    {
        $batchInsert = parent::batchInsert();

        $batchInsert['multirow']['expected'] = <<<SQL
        INSERT ALL  INTO "type" ("int_col", "float_col", "char_col", "bool_col") VALUES (:qp0, :qp1, :qp2, :qp3) INTO "type" ("int_col", "float_col", "char_col", "bool_col") VALUES (:qp4, :qp5, :qp6, :qp7) SELECT 1 FROM SYS.DUAL
        SQL;
        $batchInsert['multirow']['expectedParams'][':qp3'] = '1';
        $batchInsert['multirow']['expectedParams'][':qp7'] = '0';

        DbHelper::changeSqlForOracleBatchInsert($batchInsert['issue11242']['expected']);
        $batchInsert['issue11242']['expectedParams'][':qp3'] = '1';

        DbHelper::changeSqlForOracleBatchInsert($batchInsert['table name with column name with brackets']['expected']);
        $batchInsert['table name with column name with brackets']['expectedParams'][':qp3'] = '0';

        DbHelper::changeSqlForOracleBatchInsert($batchInsert['batchInsert binds params from expression']['expected']);
        $batchInsert['batchInsert binds params from expression']['expectedParams'][':qp3'] = '0';

        DbHelper::changeSqlForOracleBatchInsert($batchInsert['with associative values']['expected']);
        $batchInsert['with associative values']['expectedParams'][':qp3'] = '1';

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
}
