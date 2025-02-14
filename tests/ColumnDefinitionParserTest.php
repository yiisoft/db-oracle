<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\Oracle\Column\ColumnDefinitionParser;
use Yiisoft\Db\Tests\AbstractColumnDefinitionParserTest;

/**
 * @group oracle
 */
final class ColumnDefinitionParserTest extends AbstractColumnDefinitionParserTest
{
    protected function createColumnDefinitionParser(): ColumnDefinitionParser
    {
        return new ColumnDefinitionParser();
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\ColumnDefinitionParserProvider::parse
     */
    public function testParse(string $definition, array $expected): void
    {
        parent::testParse($definition, $expected);
    }
}
