<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;

final class ColumnSchemaBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnSchemaBuilderProvider
{
    protected static string $driverName = 'pgsql';

    public static function types(): array
    {
        $types = parent::types();

        $types[0][0] = 'integer UNSIGNED DEFAULT NULL NULL';
        $types[1][0] = 'integer(10) UNSIGNED';

        return [
            ...$types,
            ['integer UNSIGNED', ColumnType::INTEGER, null, [['unsigned']]],
        ];
    }

    public static function createColumnTypes(): array
    {
        $types = parent::createColumnTypes();
        $types['integer'][0] = '"column" NUMBER(10)';

        $types['uuid'][0] = '"column" RAW(16)';
        $types['uuid not null'][0] = '"column" RAW(16) NOT NULL';

        $types['uuid with default'][0] = '"column" RAW(16) DEFAULT HEXTORAW(REGEXP_REPLACE(\'875343b3-6bd0-4bec-81bb-aa68bb52d945\', \'-\', \'\'))';
        $types['uuid with default'][3] = [['defaultExpression', 'HEXTORAW(REGEXP_REPLACE(\'875343b3-6bd0-4bec-81bb-aa68bb52d945\', \'-\', \'\'))']];

        $types['uuid pk'][0] = '"column" RAW(16) DEFAULT SYS_GUID() PRIMARY KEY';
        $types['uuid pk not null'][0] = '"column" RAW(16) DEFAULT SYS_GUID() PRIMARY KEY NOT NULL';
        $types['uuid pk not null with default'][0] = '"column" RAW(16) DEFAULT SYS_GUID() PRIMARY KEY NOT NULL';
        $types['uuid pk not null with default'][3] = [['notNull']];

        return $types;
    }
}
