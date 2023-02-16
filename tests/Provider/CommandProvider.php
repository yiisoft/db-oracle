<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;

use function json_encode;
use function serialize;

final class CommandProvider extends \Yiisoft\Db\Tests\Provider\CommandProvider
{
    use TestTrait;

    public function batchInsert(): array
    {
        $batchInsert = parent::batchInsert();

        $batchInsert['multirow']['expected'] = <<<SQL
        INSERT ALL  INTO "type" ("int_col", "float_col", "char_col", "bool_col") VALUES (:qp0, :qp1, :qp2, :qp3) INTO "type" ("int_col", "float_col", "char_col", "bool_col") VALUES (:qp4, :qp5, :qp6, :qp7) SELECT 1 FROM SYS.DUAL
        SQL;
        $batchInsert['multirow']['expectedParams'][':qp3'] = '1';
        $batchInsert['multirow']['expectedParams'][':qp7'] = '0';

        $this->changeSqlForOracleBatchInsert($batchInsert['issue11242']['expected']);

        $batchInsert['issue11242']['expectedParams'][':qp3'] = '1';

        $this->changeSqlForOracleBatchInsert($batchInsert['wrongBehavior']['expected']);

        $batchInsert['wrongBehavior']['expectedParams'][':qp3'] = '0';

        $this->changeSqlForOracleBatchInsert($batchInsert['batchInsert binds params from expression']['expected']);

        $batchInsert['batchInsert binds params from expression']['expectedParams'][':qp3'] = '0';

        return $batchInsert;
    }

    public function insertVarbinary(): array
    {
        return [
            [
                json_encode(['string' => 'string', 'integer' => 1234]),
                json_encode(['string' => 'string', 'integer' => 1234]),
            ],
            [
                serialize(['string' => 'string', 'integer' => 1234]),
                new Param(serialize(['string' => 'string', 'integer' => 1234]), PDO::PARAM_LOB),
            ],
            ['simple string', 'simple string'],
        ];
    }
}
