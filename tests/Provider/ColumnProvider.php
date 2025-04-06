<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Oracle\Column\BinaryColumn;
use Yiisoft\Db\Oracle\Column\BooleanColumn;
use Yiisoft\Db\Oracle\Column\JsonColumn;

class ColumnProvider extends \Yiisoft\Db\Tests\Provider\ColumnProvider
{
    public static function predefinedTypes(): array
    {
        $values = parent::predefinedTypes();
        $values['binary'][0] = BinaryColumn::class;
        $values['boolean'][0] = BooleanColumn::class;
        $values['json'][0] = JsonColumn::class;

        return $values;
    }

    public static function dbTypecastColumns(): array
    {
        $values = parent::dbTypecastColumns();
        $values['binary'][0] = new BinaryColumn();
        $values['boolean'] = [
            new BooleanColumn(),
            [
                [null, null],
                [null, ''],
                ['1', true],
                ['1', 1],
                ['1', 1.0],
                ['1', '1'],
                ['0', false],
                ['0', 0],
                ['0', 0.0],
                ['0', '0'],
                [$expression = new Expression('expression'), $expression],
            ],
        ];
        $values['json'][0] = new JsonColumn();

        return $values;
    }

    public static function phpTypecastColumns(): array
    {
        $values = parent::phpTypecastColumns();
        $values['binary'][0] = new BinaryColumn();
        $values['boolean'][0] = new BooleanColumn();
        $values['json'][0] = new JsonColumn();

        return $values;
    }
}
