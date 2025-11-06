<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\Oracle\SqlParser;
use Yiisoft\Db\Tests\AbstractSqlParserTest;

/**
 * @group oracle
 */
final class SqlParserTest extends AbstractSqlParserTest
{
    /** @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\SqlParserProvider::getNextPlaceholder */
    public function testGetNextPlaceholder(string $sql, ?string $expectedPlaceholder, ?int $expectedPosition): void
    {
        parent::testGetNextPlaceholder($sql, $expectedPlaceholder, $expectedPosition);
    }

    protected function createSqlParser(string $sql): SqlParser
    {
        return new SqlParser($sql);
    }
}
