<?php

declare(strict_types=1);

namespace Column;

use PHPUnit\Framework\Attributes\TestWith;
use Yiisoft\Db\Oracle\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Schema\Column\EnumColumn;
use Yiisoft\Db\Tests\Common\CommonEnumColumnTest;

final class EnumColumnTest extends CommonEnumColumnTest
{
    use IntegrationTestTrait;

    #[TestWith(['INTEGER CHECK ("status" IN (1, 2, 3))'])]
    #[TestWith(["VARCHAR2(10) CHECK (\"status\" != 'abc')"])]
    #[TestWith(["VARCHAR2(10) CHECK (\"status\" NOT IN ('a', 'b', 'c'))"])]
    public function testNonEnumCheck(string $columnDefinition): void
    {
        $this->dropTable('test_enum_table');
        $this->executeStatements(
            <<<SQL
            CREATE TABLE "test_enum_table" (
                "id" INTEGER,
                "status" $columnDefinition
            )
            SQL,
        );

        $db = $this->getSharedConnection();
        $column = $db->getTableSchema('test_enum_table')->getColumn('status');

        $this->assertNotInstanceOf(EnumColumn::class, $column);

        $this->dropTable('test_enum_table');
    }

    protected function createDatabaseObjectsStatements(): array
    {
        return [
            <<<SQL
            CREATE TABLE "tbl_enum" (
                "id" NUMBER,
                "status" NVARCHAR2(8) CHECK ("status" IN ('pending', 'unactive', 'active'))
            )
            SQL,
        ];
    }

    protected function dropDatabaseObjectsStatements(): array
    {
        return [
            <<<SQL
            BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE "tbl_enum"';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;
            SQL,
        ];
    }
}
