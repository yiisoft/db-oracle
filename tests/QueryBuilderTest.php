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
use Yiisoft\Db\Oracle\PDO\QueryBuilderPDOOracle;
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
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropCheck(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::addDropForeignKeysProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropForeignKey(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::addDropPrimaryKeysProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropPrimaryKey(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::addDropUniquesProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropUnique(string $sql, Closure $builder): void
    {
        $db = $this->getConnection();
        $this->assertSame($db->getQuoter()->quoteSql($sql), $builder($db->getQueryBuilder()));
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::batchInsertProvider
     *
     * @param string $table
     * @param array $columns
     * @param array $value
     * @param string $expected
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBatchInsert(string $table, array $columns, array $value, string $expected): void
    {
        $db = $this->getConnection();
        $this->assertEquals($expected, $db->getQueryBuilder()->batchInsert($table, $columns, $value));
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::buildConditionsProvider
     *
     * @param array|ExpressionInterface $condition
     * @param string $expected
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildCondition($condition, string $expected, array $expectedParams): void
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
     * @param array $condition
     * @param string $expected
     * @param array $expectedParams
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
     * @param string $table
     * @param string $expected
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
     * @param array|ExpressionInterface $condition
     * @param string $expected
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testBuildLikeCondition($condition, string $expected, array $expectedParams): void
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
     * @param string $cond
     * @param string $expectedQuerySql
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
     *
     * @param string $sql
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
     * @param string $table
     * @param array|string $condition
     * @param string $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testDelete(string $table, $condition, string $expectedSQL, array $expectedParams): void
    {
        $actualParams = [];
        $db = $this->getConnection();
        $this->assertSame($expectedSQL, $db->getQueryBuilder()->delete($table, $condition, $actualParams));
        $this->assertSame($expectedParams, $actualParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::insertProvider
     *
     * @param string $table
     * @param array|QueryInterface $columns
     * @param array $params
     * @param string $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testInsert(string $table, $columns, array $params, string $expectedSQL, array $expectedParams): void
    {
        $actualParams = $params;
        $db = $this->getConnection();
        $actualSQL = $db->getQueryBuilder()->insert($table, $columns, $actualParams);
        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }

    public function testResetSequence()
    {
        $db = $this->getConnection();
        /** @var QueryBuilderPDOOracle $qb */
        $qb = $db->getQueryBuilder();

        $sqlResult = "SELECT last_number FROM user_sequences WHERE sequence_name = 'item_SEQ'";

        $qb->executeResetSequence('item');
        $result = $db->createCommand($sqlResult)->queryScalar();
        $this->assertEquals(6, $result);

        $qb->executeResetSequence('item', 4);
        $result = $db->createCommand($sqlResult)->queryScalar();
        $this->assertEquals(4, $result);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\QueryBuilderProvider::updateProvider
     *
     * @param string $table
     * @param array $columns
     * @param array|string $condition
     * @param string $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function testUpdate(
        string $table,
        array $columns,
        $condition,
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
     * @param string $table
     * @param array|QueryInterface $insertColumns
     * @param array|bool $updateColumns
     * @param string|string[] $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception|JsonException|NotSupportedException
     */
    public function testUpsert(string $table, $insertColumns, $updateColumns, $expectedSQL, array $expectedParams): void
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
