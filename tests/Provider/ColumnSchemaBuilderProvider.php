<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Yiisoft\Db\Schema\SchemaInterface;

final class ColumnSchemaBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnSchemaBuilderProvider
{
    public function types(): array
    {
        $types = parent::types();

        $types[0][0] = 'integer UNSIGNED DEFAULT NULL NULL';
        $types[1][0] = 'integer(10) UNSIGNED';

        return array_merge(
            $types,
            [
                ['integer UNSIGNED', SchemaInterface::TYPE_INTEGER, null, [['unsigned']]],
            ],
        );
    }
}
