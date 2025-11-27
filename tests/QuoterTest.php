<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Oracle\Tests\Provider\QuoterProvider;
use Yiisoft\Db\Oracle\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Common\CommonQuoterTest;

/**
 * @group oracle
 */
final class QuoterTest extends CommonQuoterTest
{
    use IntegrationTestTrait;

    #[DataProviderExternal(QuoterProvider::class, 'tableNameParts')]
    public function testGetTableNameParts(string $tableName, array $expected): void
    {
        parent::testGetTableNameParts($tableName, $expected);
    }

    #[DataProviderExternal(QuoterProvider::class, 'columnNames')]
    public function testQuoteColumnName(string $columnName, string $expected): void
    {
        parent::testQuoteColumnName($columnName, $expected);
    }

    #[DataProviderExternal(QuoterProvider::class, 'simpleColumnNames')]
    public function testQuoteSimpleColumnName(
        string $columnName,
        string $expectedQuotedColumnName,
        ?string $expectedUnQuotedColumnName = null,
    ): void {
        parent::testQuoteSimpleColumnName($columnName, $expectedQuotedColumnName, $expectedUnQuotedColumnName);
    }

    #[DataProviderExternal(QuoterProvider::class, 'simpleTableNames')]
    public function testQuoteTableName(string $tableName, string $expected): void
    {
        parent::testQuoteTableName($tableName, $expected);
    }
}
