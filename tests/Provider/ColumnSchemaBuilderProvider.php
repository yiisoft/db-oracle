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
}
