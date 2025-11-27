<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Oracle\Column\ColumnDefinitionParser;
use Yiisoft\Db\Oracle\Tests\Provider\ColumnDefinitionParserProvider;
use Yiisoft\Db\Tests\AbstractColumnDefinitionParserTest;

/**
 * @group oracle
 */
final class ColumnDefinitionParserTest extends AbstractColumnDefinitionParserTest
{
    #[DataProviderExternal(ColumnDefinitionParserProvider::class, 'parse')]
    public function testParse(string $definition, array $expected): void
    {
        parent::testParse($definition, $expected);
    }

    protected function createColumnDefinitionParser(): ColumnDefinitionParser
    {
        return new ColumnDefinitionParser();
    }
}
