<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

class SqlParserProvider extends \Yiisoft\Db\Tests\Provider\SqlParserProvider
{
    public static function getNextPlaceholder(): array
    {
        return [
            ...parent::getNextPlaceholder(),
            [
                "name = q'':name'' AND age = :age",
                ':age',
                28,
            ],
            [
                "name = Q'':name'' AND age = :age",
                ':age',
                28,
            ],
            [
                "name = Q'[:name]' AND age = :age",
                ':age',
                28,
            ],
            [
                "name = Q'!':name'!' AND age = :age",
                ':age',
                30,
            ],
        ];
    }
}
