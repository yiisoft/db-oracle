<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Yiisoft\Db\Oracle\Column\BinaryColumnSchema;

class ColumnBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnBuilderProvider
{
    public static function buildingMethods(): array
    {
        $values = parent::buildingMethods();

        $values['primaryKey()'][4]['getSize'] = 10;
        $values['primaryKey(false)'][4]['getSize'] = 10;
        $values['smallPrimaryKey()'][4]['getSize'] = 5;
        $values['smallPrimaryKey(false)'][4]['getSize'] = 5;
        $values['bigPrimaryKey()'][4]['getSize'] = 20;
        $values['bigPrimaryKey(false)'][4]['getSize'] = 20;
        $values['tinyint()'][4]['getSize'] = 3;
        $values['smallint()'][4]['getSize'] = 5;
        $values['integer()'][4]['getSize'] = 10;
        $values['bigint()'][4]['getSize'] = 20;
        $values['binary()'][2] = BinaryColumnSchema::class;
        $values['binary(8)'][2] = BinaryColumnSchema::class;

        return $values;
    }
}
