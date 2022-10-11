<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Closure;
use JsonException;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Oracle\QueryBuilder;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\TestSupport\TestQueryBuilderTrait;

/**
 * @group oracle
 */
final class QueryBuilderTest extends TestCase
{
    use TestQueryBuilderTrait;

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::addDropChecksProvider
     */
    public function testAddDropCheck(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::addDropForeignKeysProvider
     */
    public function testAddDropForeignKey(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::addDropPrimaryKeysProvider
     */
    public function testAddDropPrimaryKey(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::addDropUniquesProvider
     */
    public function testAddDropUnique(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::batchInsertProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBatchInsert(string $table, array $columns, array $value, string $expected, array $expectedParams = []): void
    {
        $params = [];
        $db = $this->getConnection();

        $sql = $db->getQueryBuilder()->batchInsert($table, $columns, $value, $params);

        $this->assertEquals($expected, $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::buildConditionsProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildCondition(array|ExpressionInterface|string $condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();
        $query = (new Query($db))->where($condition);
        [$sql, $params] = $db->getQueryBuilder()->build($query);
        $replaceQuotes = $this->replaceQuotes($expected);

        $this->assertIsString($replaceQuotes);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $replaceQuotes), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::buildFilterConditionProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildFilterCondition(array $condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();
        $query = (new Query($db))->filterWhere($condition);
        [$sql, $params] = $db->getQueryBuilder()->build($query);
        $replaceQuotes = $this->replaceQuotes($expected);

        $this->assertIsString($replaceQuotes);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $replaceQuotes), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::buildFromDataProvider
     *
     * @throws Exception
     */
    public function testBuildFrom(string $table, string $expected): void
    {
        $db = $this->getConnection();
        $params = [];
        $sql = $db->getQueryBuilder()->buildFrom([$table], $params);
        $replaceQuotes = $this->replaceQuotes($expected);

        $this->assertIsString($replaceQuotes);
        $this->assertEquals('FROM ' . $replaceQuotes, $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::buildLikeConditionsProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildLikeCondition(array|ExpressionInterface $condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();
        $query = (new Query($db))->where($condition);
        [$sql, $params] = $db->getQueryBuilder()->build($query);
        $replaceQuotes = $this->replaceQuotes($expected);

        $this->assertIsString($replaceQuotes);
        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $replaceQuotes), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::buildExistsParamsProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildWhereExists(string $cond, string $expectedQuerySql): void
    {
        $db = $this->getConnection();
        $expectedQueryParams = [];
        $subQuery = new Query($db);
        $subQuery->select('1')->from('Website w');
        $query = new Query($db);
        $query->select('id')->from('TotalExample t')->where([$cond, $subQuery]);
        [$actualQuerySql, $actualQueryParams] = $db->getQueryBuilder()->build($query);
        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $actualQueryParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::createDropIndexesProvider
     */
    public function testCreateDropIndex(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    public function testCommentColumn()
    {
        $db = $this->getConnection();
        $ddl = $db->getQueryBuilder();

        $expected = "COMMENT ON COLUMN [[comment]].[[text]] IS 'This is my column.'";
        $sql = $ddl->addCommentOnColumn('comment', 'text', 'This is my column.');
        $this->assertEquals($this->replaceQuotes($expected), $sql);

        $expected = "COMMENT ON COLUMN [[comment]].[[text]] IS ''";
        $sql = $ddl->dropCommentFromColumn('comment', 'text');
        $this->assertEquals($this->replaceQuotes($expected), $sql);
    }

    public function testCommentTable()
    {
        $db = $this->getConnection();
        $ddl = $db->getQueryBuilder();

        $expected = "COMMENT ON TABLE [[comment]] IS 'This is my table.'";
        $sql = $ddl->addCommentOnTable('comment', 'This is my table.');
        $this->assertEquals($this->replaceQuotes($expected), $sql);

        $expected = "COMMENT ON TABLE [[comment]] IS ''";
        $sql = $ddl->dropCommentFromTable('comment');
        $this->assertEquals($this->replaceQuotes($expected), $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::deleteProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testDelete(string $table, array|string $condition, string $expectedSQL, array $expectedParams): void
    {
        $actualParams = [];
        $db = $this->getConnection();
        $this->assertSame($expectedSQL, $db->getQueryBuilder()->delete($table, $condition, $actualParams));
        $this->assertSame($expectedParams, $actualParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::insertProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testInsert(string $table, array|QueryInterface $columns, array $params, string $expectedSQL, array $expectedParams): void
    {
        $actualParams = $params;
        $db = $this->getConnection();
        $actualSQL = $db->getQueryBuilder()->insert($table, $columns, $actualParams);
        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }

    public function testResetSequence(): void
    {
        $db = $this->getConnection(true);

        /** @var QueryBuilder $qb */
        $qb = $db->getQueryBuilder();

        $checkSql = "SELECT last_number FROM user_sequences WHERE sequence_name = 'item_SEQ'";

        $sql = $qb->resetSequence('item');
        $expected = <<<SQL
declare
    lastSeq number;
begin
    SELECT MAX("id") + 1 INTO lastSeq FROM "item";
    if lastSeq IS NULL then lastSeq := 1; end if;
    execute immediate 'DROP SEQUENCE "item_SEQ"';
    execute immediate 'CREATE SEQUENCE "item_SEQ" START WITH ' || lastSeq || ' INCREMENT BY 1 NOMAXVALUE NOCACHE';
end;
SQL;
        $this->assertEquals($expected, $sql);

        $db->createCommand($sql)->execute();
        $result = $db->createCommand($checkSql)->queryScalar();
        $this->assertEquals(6, $result);

        $sql = $qb->resetSequence('item', 4);
        $expected = <<<SQL
declare
    lastSeq number := 4;
begin
    if lastSeq IS NULL then lastSeq := 1; end if;
    execute immediate 'DROP SEQUENCE "item_SEQ"';
    execute immediate 'CREATE SEQUENCE "item_SEQ" START WITH ' || lastSeq || ' INCREMENT BY 1 NOMAXVALUE NOCACHE';
end;
SQL;
        $this->assertEquals($expected, $sql);

        $db->createCommand($sql)->execute();
        $result = $db->createCommand($checkSql)->queryScalar();
        $this->assertEquals(4, $result);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::updateProvider
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $condition,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $actualParams = [];
        $db = $this->getConnection();
        $this->assertSame($expectedSQL, $db->getQueryBuilder()->update($table, $columns, $condition, $actualParams));
        $this->assertSame($expectedParams, $actualParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::upsertProvider
     *
     * @param string|string[] $expectedSQL
     *
     * @throws Exception|JsonException|NotSupportedException
     */
    public function testUpsert(string $table, array|QueryInterface $insertColumns, array|bool $updateColumns, string|array $expectedSQL, array $expectedParams): void
    {
        $actualParams = [];
        $db = $this->getConnection();
        $actualSQL = $db->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns, $actualParams);

        if (is_string($expectedSQL)) {
            $this->assertEqualsWithoutLE($expectedSQL, $actualSQL);
        } else {
            $this->assertContains($actualSQL, $expectedSQL);
        }

        if (ArrayHelper::isAssociative($expectedParams)) {
            $this->assertSame($expectedParams, $actualParams);
        } else {
            $this->assertIsOneOf($actualParams, $expectedParams);
        }
    }
}
