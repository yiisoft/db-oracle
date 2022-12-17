<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractQuoterTest;

/**
 * @group oracle
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QuoterTest extends AbstractQuoterTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QuoterProvider::tableNameParts()
     */
    public function testGetTableNameParts(string $tableName, string ...$expected): void
    {
        parent::testGetTableNameParts($tableName, ...$expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QuoterProvider::columnNames()
     */
    public function testQuoteColumnName(string $columnName, string $expected): void
    {
        parent::testQuoteColumnName($columnName, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QuoterProvider::simpleColumnNames()
     */
    public function testQuoteSimpleColumnName(
        string $columnName,
        string $expectedQuotedColumnName,
        string $expectedUnQuotedColumnName
    ): void {
        parent::testQuoteSimpleColumnName($columnName, $expectedQuotedColumnName, $expectedUnQuotedColumnName);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QuoterProvider::simpleTableNames()
     */
    public function testQuoteTableName(string $tableName, string $expected): void
    {
        parent::testQuoteTableName($tableName, $expected);
    }
}
