<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

class ColumnDefinitionParserProvider extends \Yiisoft\Db\Tests\Provider\ColumnDefinitionParserProvider
{
    public static function parse(): array
    {
        return [
            ...parent::parse(),
            ['interval day to second', ['type' => 'interval day to second']],
            ['interval day to second (2)', ['type' => 'interval day to second', 'size' => 2]],
            ['interval day(0) to second(2)', ['type' => 'interval day(0) to second', 'size' => 2]],
            ['timestamp with time zone', ['type' => 'timestamp with time zone']],
            ['timestamp (3) with time zone', ['type' => 'timestamp (3) with time zone', 'size' => 3]],
            ['timestamp(3) with local time zone', ['type' => 'timestamp(3) with local time zone', 'size' => 3]],
            ['interval year to month', ['type' => 'interval year to month']],
        ];
    }
}
