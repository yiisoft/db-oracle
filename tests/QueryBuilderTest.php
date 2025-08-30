<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\TestWith;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Value\ArrayExpression;
use Yiisoft\Db\Expression\Statement\CaseExpression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\ArrayColumn;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Tests\Common\CommonQueryBuilderTest;
use Yiisoft\Db\Tests\Support\Assert;

/**
 * @group oracle
 */
final class QueryBuilderTest extends CommonQueryBuilderTest
{
    use TestTrait;

    public function getBuildColumnDefinitionProvider(): array
    {
        return QueryBuilderProvider::buildColumnDefinition();
    }

    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Oracle\DDLQueryBuilder::addDefaultValue is not supported by Oracle.');

        $qb->addDefaultValue('T_constraints_1', 'CN_pk', 'C_default', 1);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'addForeignKey')]
    public function testAddForeignKey(
        string $name,
        string $table,
        array|string $columns,
        string $refTable,
        array|string $refColumns,
        string|null $delete,
        string|null $update,
        string $expected
    ): void {
        // Oracle does not support ON UPDATE CASCADE
        parent::testAddForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, null, $expected);
    }

    public function testAddForeignKeyUpdateException(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Oracle does not support ON UPDATE clause.');

        $qb->addForeignKey('T_constraints_1', 'fk1', 'C_fk1', 'T_constraints_2', 'C_fk2', 'CASCADE', 'CASCADE');
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'addPrimaryKey')]
    public function testAddPrimaryKey(string $name, string $table, array|string $columns, string $expected): void
    {
        parent::testAddPrimaryKey($name, $table, $columns, $expected);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'addUnique')]
    public function testAddUnique(string $name, string $table, array|string $columns, string $expected): void
    {
        parent::testAddUnique($name, $table, $columns, $expected);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'alterColumn')]
    public function testAlterColumn(string|ColumnInterface $type, string $expected): void
    {
        parent::testAlterColumn($type, $expected);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'batchInsert')]
    public function testBatchInsert(
        string $table,
        iterable $rows,
        array $columns,
        string $expected,
        array $expectedParams = [],
    ): void {
        parent::testBatchInsert($table, $rows, $columns, $expected, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildCondition')]
    public function testBuildCondition(
        array|ExpressionInterface|string $condition,
        string|null $expected,
        array $expectedParams
    ): void {
        parent::testBuildCondition($condition, $expected, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildLikeCondition')]
    public function testBuildLikeCondition(
        array|ExpressionInterface $condition,
        string $expected,
        array $expectedParams
    ): void {
        parent::testBuildLikeCondition($condition, $expected, $expectedParams);
    }

    public function testBuildOrderByAndLimit(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))
            ->from('admin_user')
            ->orderBy(['id' => SORT_ASC, 'name' => SORT_DESC])
            ->limit(10)
            ->offset(5);

        $this->assertSame(
            <<<SQL
            WITH USER_SQL AS (SELECT * FROM admin_user ORDER BY "id", "name" DESC), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
            SELECT * FROM PAGINATION WHERE rowNumId > 5 AND rownum <= 10
            SQL,
            $qb->buildOrderByAndLimit(
                <<<SQL
                SELECT * FROM admin_user
                SQL,
                $query->getOrderBy(),
                $query->getLimit(),
                $query->getOffset(),
            ),
        );

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildFrom')]
    public function testBuildWithFrom(mixed $table, string $expectedSql, array $expectedParams = []): void
    {
        parent::testBuildWithFrom($table, $expectedSql, $expectedParams);
    }

    public function testBuildWithLimit(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->limit(10);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            <<<SQL
            WITH USER_SQL AS (SELECT * FROM DUAL), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
            SELECT * FROM PAGINATION WHERE rownum <= 10
            SQL,
            $sql,
        );
        $this->assertSame([], $params);

        $db->close();
    }

    public function testBuildWithOffset(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->offset(10);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            <<<SQL
            WITH USER_SQL AS (SELECT * FROM DUAL), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
            SELECT * FROM PAGINATION WHERE rowNumId > 10
            SQL,
            $sql,
        );
        $this->assertSame([], $params);

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildWhereExists')]
    public function testBuildWithWhereExists(string $cond, string $expectedQuerySql): void
    {
        parent::testBuildWithWhereExists($cond, $expectedQuerySql);
    }

    public function testCheckIntegrity(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Oracle\DDLQueryBuilder::checkIntegrity is not supported by Oracle.');

        $qb->checkIntegrity('', 'customer');
    }

    public function testCreateTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            CREATE TABLE "test" (
            \t"id" number(10) GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
            \t"name" varchar2(255) NOT NULL,
            \t"email" varchar2(255) NOT NULL,
            \t"status" number(10) NOT NULL,
            \t"created_at" timestamp NOT NULL
            )
            SQL,
            $qb->createTable(
                'test',
                [
                    'id' => 'pk',
                    'name' => 'string(255) NOT NULL',
                    'email' => 'string(255) NOT NULL',
                    'status' => 'integer NOT NULL',
                    'created_at' => 'datetime NOT NULL',
                ],
            ),
        );

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'delete')]
    public function testDelete(string $table, array|string $condition, string $expectedSQL, array $expectedParams): void
    {
        parent::testDelete($table, $condition, $expectedSQL, $expectedParams);
    }

    public function testDropCommentFromColumn(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            COMMENT ON COLUMN "customer"."id" IS ''
            SQL,
            $qb->dropCommentFromColumn('customer', 'id'),
        );

        $db->close();
    }

    public function testDropCommentFromTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            COMMENT ON TABLE "customer" IS ''
            SQL,
            $qb->dropCommentFromTable('customer'),
        );

        $db->close();
    }

    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Oracle\DDLQueryBuilder::dropDefaultValue is not supported by Oracle.'
        );

        $qb->dropDefaultValue('T_constraints_1', 'CN_pk');
    }

    public function testDropIndex(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            DROP INDEX "CN_constraints_2_single"
            SQL,
            $qb->dropIndex('T_constraints_2', 'CN_constraints_2_single'),
        );

        $db->close();
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'insert')]
    public function testInsert(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsert($table, $columns, $params, $expectedSQL, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'insertReturningPks')]
    public function testInsertReturningPks(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Oracle\DMLQueryBuilder::insertReturningPks is not supported by Oracle.',
        );

        $db = $this->getConnection(true);
        $qb = $db->getQueryBuilder();
        $qb->insertReturningPks($table, $columns, $params);
    }

    public function testRenameTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            ALTER TABLE "alpha" RENAME TO "alpha-test"
            SQL,
            $qb->renameTable('alpha', 'alpha-test'),
        );

        $db->close();
    }

    public function testResetSequence(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $qb = $db->getQueryBuilder();

        $checkSql = <<<SQL
        SELECT last_number FROM user_sequences WHERE sequence_name = 'item_SEQ'
        SQL;
        $sql = $qb->resetSequence('item');

        $this->assertSame(
            <<<SQL
            declare
                lastSeq number;
            begin
                SELECT MAX("id") + 1 INTO lastSeq FROM "item";
                if lastSeq IS NULL then lastSeq := 1; end if;
                execute immediate 'DROP SEQUENCE "item_SEQ"';
                execute immediate 'CREATE SEQUENCE "item_SEQ" START WITH ' || lastSeq || ' INCREMENT BY 1 NOMAXVALUE NOCACHE';
            end;
            SQL,
            $sql,
        );

        $command->setSql($sql)->execute();

        $this->assertSame('6', $command->setSql($checkSql)->queryScalar());

        $sql = $qb->resetSequence('item', 4);

        $this->assertSame(
            <<<SQL
            declare
                lastSeq number := 4;
            begin
                if lastSeq IS NULL then lastSeq := 1; end if;
                execute immediate 'DROP SEQUENCE "item_SEQ"';
                execute immediate 'CREATE SEQUENCE "item_SEQ" START WITH ' || lastSeq || ' INCREMENT BY 1 NOMAXVALUE NOCACHE';
            end;
            SQL,
            $sql,
        );

        $command->setSql($sql)->execute();

        $this->assertEquals(4, $command->setSql($checkSql)->queryScalar());

        $sql = $qb->resetSequence('item', '1');

        $this->assertSame(
            <<<SQL
            declare
                lastSeq number := 1;
            begin
                if lastSeq IS NULL then lastSeq := 1; end if;
                execute immediate 'DROP SEQUENCE "item_SEQ"';
                execute immediate 'CREATE SEQUENCE "item_SEQ" START WITH ' || lastSeq || ' INCREMENT BY 1 NOMAXVALUE NOCACHE';
            end;
            SQL,
            $sql,
        );

        $command->setSql($sql)->execute();

        $this->assertSame('1', $db->createCommand($checkSql)->queryScalar());

        $db->close();
    }

    public function testResetNonExistSequenceException(): void
    {
        $db = $this->getConnection(true);
        $qb = $db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("There is not sequence associated with table 'default_multiple_pk'.");
        $qb->resetSequence('default_multiple_pk');

        $db->close();
    }

    public function testResetSequenceCompositeException(): void
    {
        self::markTestSkipped('Sequence name not found for composite primary key');

        $db = $this->getConnection(true);
        $qb = $db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Can't reset sequence for composite primary key in table: employee");
        $qb->resetSequence('employee');

        $db->close();
    }

    public function testSelectExists(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $sql = 'SELECT 1 FROM "customer" WHERE "id" = 1';
        // Alias is not required in Oracle, but it is added for consistency with other DBMS.
        $expected = 'SELECT CASE WHEN EXISTS(SELECT 1 FROM "customer" WHERE "id" = 1) THEN 1 ELSE 0 END AS "0" FROM DUAL';

        $this->assertSame($expected, $qb->selectExists($sql));
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'update')]
    public function testUpdate(
        string $table,
        array $columns,
        array|string $condition,
        array $params,
        string $expectedSql,
        array $expectedParams = [],
    ): void {
        parent::testUpdate($table, $columns, $condition, $params, $expectedSql, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'upsert')]
    public function testUpsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string $expectedSql,
        array $expectedParams
    ): void {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSql, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'upsertReturning')]
    public function testUpsertReturning(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        array|null $returnColumns,
        string $expectedSql,
        array $expectedParams
    ): void {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Oracle\DMLQueryBuilder::upsertReturning() is not supported by Oracle.');

        $qb->upsertReturning($table, $insertColumns, $updateColumns);
    }

    public function testDefaultValues(): void
    {
        $db = $this->getConnection();
        $queryBuilder = $db->getQueryBuilder();

        // Non-primary key columns should have DEFAULT as value
        $this->assertSame(
            'INSERT INTO "negative_default_values" ("tinyint_col") VALUES (DEFAULT)',
            $queryBuilder->insert('negative_default_values', []),
        );
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'selectScalar')]
    public function testSelectScalar(array|bool|float|int|string $columns, string $expected): void
    {
        parent::testSelectScalar($columns, $expected);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildColumnDefinition')]
    public function testBuildColumnDefinition(string $expected, ColumnInterface|string $column): void
    {
        parent::testBuildColumnDefinition($expected, $column);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'buildValue')]
    public function testBuildValue(mixed $value, string $expected, array $expectedParams = []): void
    {
        parent::testBuildValue($value, $expected, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'prepareParam')]
    public function testPrepareParam(string $expected, mixed $value, int $type): void
    {
        parent::testPrepareParam($expected, $value, $type);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'prepareValue')]
    public function testPrepareValue(string $expected, mixed $value): void
    {
        parent::testPrepareValue($expected, $value);
    }

    #[DataProvider('dataDropTable')]
    public function testDropTable(string $expected, ?bool $ifExists, ?bool $cascade): void
    {
        if ($ifExists) {
            $qb = $this->getConnection()->getQueryBuilder();

            $this->expectException(NotSupportedException::class);
            $this->expectExceptionMessage('Oracle doesn\'t support "IF EXISTS" option on drop table.');

            $cascade === null
                ? $qb->dropTable('customer', ifExists: true)
                : $qb->dropTable('customer', ifExists: true, cascade: $cascade);

            return;
        }

        if ($cascade) {
            $expected = str_replace('CASCADE', 'CASCADE CONSTRAINTS', $expected);
        }

        parent::testDropTable($expected, $ifExists, $cascade);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'caseExpressionBuilder')]
    public function testCaseExpressionBuilder(
        CaseExpression $case,
        string $expectedSql,
        array $expectedParams,
        string|int $expectedResult,
    ): void {
        parent::testCaseExpressionBuilder($case, $expectedSql, $expectedParams, $expectedResult);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'lengthBuilder')]
    public function testLengthBuilder(
        string|ExpressionInterface $operand,
        string $expectedSql,
        int $expectedResult,
        array $expectedParams = [],
    ): void {
        parent::testLengthBuilder($operand, $expectedSql, $expectedResult, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'multiOperandFunctionBuilder')]
    public function testMultiOperandFunctionBuilder(
        string $class,
        array $operands,
        string $expectedSql,
        array|string|int $expectedResult,
        array $expectedParams = [],
    ): void {
        parent::testMultiOperandFunctionBuilder($class, $operands, $expectedSql, $expectedResult, $expectedParams);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'multiOperandFunctionClasses')]
    public function testMultiOperandFunctionBuilderWithoutOperands(string $class): void
    {
        parent::testMultiOperandFunctionBuilderWithoutOperands($class);
    }

    #[TestWith(['int[]', 'int', '[1,2,3,4,5,6,7,9,10]'])]
    #[TestWith([new IntegerColumn(), 'number(10)', '[1,2,3,4,5,6,7,9,10]'])]
    #[TestWith([new ArrayColumn(), '', '["1","10","2","3","4","5","6","7","9"]'])]
    #[TestWith([new ArrayColumn(column: new IntegerColumn()), 'number(10)', '[1,2,3,4,5,6,7,9,10]'])]
    public function testArrayMergeWithTypeWithOrdering(
        string|ColumnInterface $type,
        string $operandType,
        string $expectedResult,
    ): void {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $stringParam = new Param('[4,3,5]', DataType::STRING);
        $arrayMerge = (new ArrayMerge(
            "'[2,1,3]'",
            [6, 5, 7],
            $stringParam,
            self::getDb()->select(new ArrayExpression([10, 9])),
        ))->type($type)->ordered();
        $params = [];

        $this->assertSame(
            '(SELECT JSON_ARRAYAGG(value ORDER BY value) AS value FROM ('
            . "SELECT value FROM JSON_TABLE('[2,1,3]', '$[*]' COLUMNS(value $operandType PATH '$'))"
            . " UNION SELECT value FROM JSON_TABLE(:qp0, '$[*]' COLUMNS(value $operandType PATH '$'))"
            . " UNION SELECT value FROM JSON_TABLE(:qp1, '$[*]' COLUMNS(value $operandType PATH '$'))"
            . " UNION SELECT value FROM JSON_TABLE((SELECT :qp2 FROM DUAL), '$[*]' COLUMNS(value $operandType PATH '$'))"
            . '))',
            $qb->buildExpression($arrayMerge, $params)
        );
        Assert::arraysEquals(
            [
                ':qp0' => new Param('[6,5,7]', DataType::STRING),
                ':qp1' => $stringParam,
                ':qp2' => new Param('[10,9]', DataType::STRING),
            ],
            $params,
        );

        $result = $db->select($arrayMerge)->scalar();

        $this->assertSame($expectedResult, $result);
    }

    #[DataProviderExternal(QueryBuilderProvider::class, 'upsertWithMultiOperandFunctions')]
    public function testUpsertWithMultiOperandFunctions(
        array $initValues,
        array $insertValues,
        array $updateValues,
        string $expectedSql,
        array $expectedResult,
        array $expectedParams = [],
    ): void {
        parent::testUpsertWithMultiOperandFunctions($initValues, $insertValues, $updateValues, $expectedSql, $expectedResult, $expectedParams);
    }
}
