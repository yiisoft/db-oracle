<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Tests\Common\CommonQueryBuilderTest;

/**
 * @group oracle
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryBuilderTest extends CommonQueryBuilderTest
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Oracle\DDLQueryBuilder::addDefaultValue is not supported by Oracle.');

        $qb->addDefaultValue('CN_pk', 'T_constraints_1', 'C_default', 1);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::addForeignKey()
     */
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

        $qb->addForeignKey('fk1', 'T_constraints_1', 'C_fk1', 'T_constraints_2', 'C_fk2', 'CASCADE', 'CASCADE');
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::addPrimaryKey()
     */
    public function testAddPrimaryKey(string $name, string $table, array|string $columns, string $expected): void
    {
        parent::testAddPrimaryKey($name, $table, $columns, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::addUnique()
     */
    public function testAddUnique(string $name, string $table, array|string $columns, string $expected): void
    {
        parent::testAddUnique($name, $table, $columns, $expected);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testAlterColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $schema = $db->getSchema();

        $this->assertSame(
            <<<SQL
            ALTER TABLE "customer" MODIFY "email" VARCHAR2(255)
            SQL,
            $qb->alterColumn('customer', 'email', (string) $schema::TYPE_STRING),
        );

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::batchInsert()
     */
    public function testBatchInsert(string $table, array $columns, array $rows, string $expected): void
    {
        parent::testBatchInsert($table, $columns, $rows, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::buildCondition()
     */
    public function testBuildCondition(
        array|ExpressionInterface|string $condition,
        string|null $expected,
        array $expectedParams
    ): void {
        parent::testBuildCondition($condition, $expected, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::buildLikeCondition()
     */
    public function testBuildLikeCondition(
        array|ExpressionInterface $condition,
        string $expected,
        array $expectedParams
    ): void {
        parent::testBuildLikeCondition($condition, $expected, $expectedParams);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     */
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
            /** @noRector \Rector\Php73\Rector\String_\SensitiveHereNowDocRector */
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

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::buildFrom()
     */
    public function testBuildWithFrom(mixed $table, string $expectedSql, array $expectedParams = []): void
    {
        parent::testBuildWithFrom($table, $expectedSql, $expectedParams);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithLimit(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->limit(10);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            /** @noRector \Rector\Php73\Rector\String_\SensitiveHereNowDocRector */
            <<<SQL
            WITH USER_SQL AS (SELECT *), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
            SELECT * FROM PAGINATION WHERE rownum <= 10
            SQL,
            $sql,
        );
        $this->assertSame([], $params);

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function testBuildWithOffset(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->offset(10);

        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            /** @noRector \Rector\Php73\Rector\String_\SensitiveHereNowDocRector */
            <<<SQL
            WITH USER_SQL AS (SELECT *), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
            SELECT * FROM PAGINATION WHERE rowNumId > 10
            SQL,
            $sql,
        );
        $this->assertSame([], $params);

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::buildWhereExists()
     */
    public function testBuildWithWhereExists(string $cond, string $expectedQuerySql): void
    {
        parent::testBuildWithWhereExists($cond, $expectedQuerySql);
    }

    /**
     * @throws Exception
     * @throws NotSupportedException
     */
    public function testCheckIntegrity(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Oracle\DDLQueryBuilder::checkIntegrity is not supported by Oracle.');

        $qb->checkIntegrity('', 'customer');
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testCreateTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            CREATE TABLE "test" (
            \t"id" NUMBER(10) NOT NULL PRIMARY KEY,
            \t"name" VARCHAR2(255) NOT NULL,
            \t"email" VARCHAR2(255) NOT NULL,
            \t"status" NUMBER(10) NOT NULL,
            \t"created_at" TIMESTAMP NOT NULL
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

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::delete()
     */
    public function testDelete(string $table, array|string $condition, string $expectedSQL, array $expectedParams): void
    {
        parent::testDelete($table, $condition, $expectedSQL, $expectedParams);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Oracle\DDLQueryBuilder::dropDefaultValue is not supported by Oracle.'
        );

        $qb->dropDefaultValue('CN_pk', 'T_constraints_1');
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testDropIndex(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            DROP INDEX "CN_constraints_2_single"
            SQL,
            $qb->dropIndex('CN_constraints_2_single', 'T_constraints_2'),
        );

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::insert()
     */
    public function testInsert(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testInsert($table, $columns, $params, $expectedSQL, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::insertEx()
     */
    public function testInsertEx(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\DMLQueryBuilder::insertEx() is not supported by this DBMS.',
        );

        $db = $this->getConnection(true);
        $qb = $db->getQueryBuilder();
        $qb->insertEx($table, $columns, $params);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
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

    public function testResetSequenceCompositeException(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Can't reset sequence for composite primary key in table: default_multiple_pk");

        $qb->resetSequence('default_multiple_pk');
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::selectExist()
     */
    public function testSelectExists(string $sql, string $expected): void
    {
        parent::testSelectExists($sql, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::update()
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $condition,
        string $expectedSQL,
        array $expectedParams
    ): void {
        parent::testUpdate($table, $columns, $condition, $expectedSQL, $expectedParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::upsert()
     */
    public function testUpsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string|array $expectedSQL,
        array $expectedParams
    ): void {
        parent::testUpsert($table, $insertColumns, $updateColumns, $expectedSQL, $expectedParams);
    }
}
