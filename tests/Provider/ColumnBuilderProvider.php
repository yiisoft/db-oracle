<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Yiisoft\Db\Oracle\Column\BinaryColumn;

class ColumnBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnBuilderProvider
{
    public static function buildingMethods(): array
    {
        $values = parent::buildingMethods();

        $values['binary()'][2] = BinaryColumn::class;
        $values['binary(8)'][2] = BinaryColumn::class;

        return $values;
    }
}
