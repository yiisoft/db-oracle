<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\TestSupport\TestColumnSchemaBuilderTrait;
use Yiisoft\Db\Oracle\Schema;

/**
 * @group oracle
 */
final class ColumnSchemaBuilderTest extends TestCase
{
    use TestColumnSchemaBuilderTrait;

    /**
     * @return array
     */
    public function typesProvider()
    {
        return [
            ['integer UNSIGNED', Schema::TYPE_INTEGER, null, [
                ['unsigned'],
            ]],
            ['integer(10) UNSIGNED', Schema::TYPE_INTEGER, 10, [
                ['unsigned'],
            ]],
        ];
    }

    /**
     * @dataProvider typesProviderTrait
     *
     */
    public function testCustomTypes(string $expected, string $type, ?int $length, mixed $calls): void
    {
        $this->checkBuildString($expected, $type, $length, $calls);
    }
}
