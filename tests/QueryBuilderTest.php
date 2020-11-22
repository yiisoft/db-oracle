<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Closure;
use Yiisoft\Arrays\ArrayHelper;
use yiisoft\Db\Query\Query;
use Yiisoft\Db\Oracle\QueryBuilder;
use Yiisoft\Db\TestUtility\TestQueryBuilderTrait;

/**
 * @group oracle
 */
final class QueryBuilderTest extends TestCase
{
    use TestQueryBuilderTrait;

    protected string $likeEscapeCharSql = " ESCAPE '!'";

    protected array $likeParameterReplacements = [
        '\%' => '!%',
        '\_' => '!_',
        '!' => '!!'
    ];

    protected function getQueryBuilder(bool $reset = false): QueryBuilder
    {
        return new QueryBuilder($this->getConnection($reset));
    }

    /**
     * @dataProvider addDropChecksProviderTrait
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropCheck(string $sql, Closure $builder): void
    {
        $this->assertSame($this->getConnection()->quoteSql($sql), $builder($this->getQueryBuilder()));
    }

    public function addDropForeignKeysProvider()
    {
        $tableName = 'T_constraints_3';
        $name = 'CN_constraints_3';
        $pkTableName = 'T_constraints_2';
        return [
            'drop' => [
                "ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]",
                function (QueryBuilder $qb) use ($tableName, $name) {
                    return $qb->dropForeignKey($name, $tableName);
                },
            ],
            'add' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]]) REFERENCES {{{$pkTableName}}} ([[C_id_1]]) ON DELETE CASCADE",
                function (QueryBuilder $qb) use ($tableName, $name, $pkTableName) {
                    return $qb->addForeignKey($name, $tableName, 'C_fk_id_1', $pkTableName, 'C_id_1', 'CASCADE');
                },
            ],
            'add (2 columns)' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]], [[C_fk_id_2]]) REFERENCES {{{$pkTableName}}} ([[C_id_1]], [[C_id_2]]) ON DELETE CASCADE",
                function (QueryBuilder $qb) use ($tableName, $name, $pkTableName) {
                    return $qb->addForeignKey($name, $tableName, 'C_fk_id_1, C_fk_id_2', $pkTableName, 'C_id_1, C_id_2', 'CASCADE');
                },
            ],
        ];
    }

    /**
     * @dataProvider addDropForeignKeysProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropForeignKey(string $sql, Closure $builder): void
    {
        $this->assertSame($this->getConnection()->quoteSql($sql), $builder($this->getQueryBuilder()));
    }

    /**
     * @dataProvider addDropPrimaryKeysProviderTrait
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropPrimaryKey(string $sql, Closure $builder): void
    {
        $this->assertSame($this->getConnection()->quoteSql($sql), $builder($this->getQueryBuilder()));
    }

    /**
     * @dataProvider addDropUniquesProviderTrait
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropUnique(string $sql, Closure $builder): void
    {
        $this->assertSame($this->getConnection()->quoteSql($sql), $builder($this->getQueryBuilder()));
    }

    public function batchInsertProvider(): array
    {
        $data = $this->batchInsertProviderTrait();

        $data[0][3] = 'INSERT ALL  INTO "customer" ("email", "name", "address") ' .
            "VALUES ('test@example.com', 'silverfire', 'Kyiv {{city}}, Ukraine') SELECT 1 FROM SYS.DUAL";

        $data['escape-danger-chars']['expected'] = 'INSERT ALL  INTO "customer" ("address") ' .
            "VALUES ('SQL-danger chars are escaped: ''); --') SELECT 1 FROM SYS.DUAL";

        $data[2][3] = 'INSERT ALL  INTO "customer" () ' .
            "VALUES ('no columns passed') SELECT 1 FROM SYS.DUAL";

        $data['bool-false, bool2-null'][1] = ['[[bool_col]]', '[[bool_col2]]'];
        $data['bool-false, bool2-null']['expected'] = 'INSERT ALL  INTO "type" ([[bool_col]], [[bool_col2]]) ' .
            'VALUES (0, NULL) SELECT 1 FROM SYS.DUAL';

        $data[3][3] = 'INSERT ALL  INTO {{%type}} ({{%type}}.[[float_col]], [[time]]) ' .
            "VALUES (NULL, now()) SELECT 1 FROM SYS.DUAL";

        $data['bool-false, time-now()']['expected'] = 'INSERT ALL  INTO {{%type}} ({{%type}}.[[bool_col]], [[time]]) ' .
            "VALUES (0, now()) SELECT 1 FROM SYS.DUAL";

        return $data;
    }

    /**
     * @dataProvider batchInsertProvider
     *
     * @param string $table
     * @param array $columns
     * @param $value
     * @param string $expected
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBatchInsert(string $table, array $columns, array $value, string $expected): void
    {
        $queryBuilder = $this->getQueryBuilder();

        $sql = $queryBuilder->batchInsert($table, $columns, $value);

        $this->assertEquals($expected, $sql);
    }

    /**
     * @dataProvider buildConditionsProviderTrait
     *
     * @param array|ExpressionInterface $condition
     * @param string $expected
     * @param array $expectedParams
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBuildCondition($condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->where($condition);

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider buildFilterConditionProviderTrait
     *
     * @param array $condition
     * @param string $expected
     * @param array $expectedParams
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBuildFilterCondition(array $condition, string $expected, array $expectedParams): void
    {
        $query = (new Query($this->getConnection()))->filterWhere($condition);

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider buildFromDataProviderTrait
     *
     * @param string $table
     * @param string $expected
     *
     * @throws Exception
     */
    public function testBuildFrom(string $table, string $expected): void
    {
        $params = [];

        $sql = $this->getQueryBuilder()->buildFrom([$table], $params);

        $this->assertEquals('FROM ' . $this->replaceQuotes($expected), $sql);
    }

    public function buildLikeConditionsProvider(): array
    {
        /*
         * Different pdo_oci8 versions may or may not implement PDO::quote(), so
         * \Yiisoft\Db\Schema\Schema::quoteValue() may or may not quote \.
         */
        try {
            $encodedBackslash = substr($this->getDb()->quoteValue('\\\\'), 1, -1);
            $this->likeParameterReplacements[$encodedBackslash] = '\\';
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not execute Connection::quoteValue() method: ' . $e->getMessage());
        }

        return $this->buildLikeConditionsProviderTrait();
    }

    /**
     * @dataProvider buildLikeConditionsProvider
     *
     * @param array|object $condition
     * @param string $expected
     * @param array $expectedParams
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBuildLikeCondition($condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->where($condition);

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @dataProvider buildExistsParamsProviderTrait
     *
     * @param string $cond
     * @param string $expectedQuerySql
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testBuildWhereExists(string $cond, string $expectedQuerySql): void
    {
        $db = $this->getConnection();

        $expectedQueryParams = [];

        $subQuery = new Query($db);

        $subQuery->select('1')
            ->from('Website w');

        $query = new Query($db);

        $query->select('id')
            ->from('TotalExample t')
            ->where([$cond, $subQuery]);

        [$actualQuerySql, $actualQueryParams] = $this->getQueryBuilder()->build($query);

        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $actualQueryParams);
    }

    public function testCommentColumn()
    {
        $qb = $this->getQueryBuilder();

        $expected = "COMMENT ON COLUMN [[comment]].[[text]] IS 'This is my column.'";
        $sql = $qb->addCommentOnColumn('comment', 'text', 'This is my column.');
        $this->assertEquals($this->replaceQuotes($expected), $sql);

        $expected = "COMMENT ON COLUMN [[comment]].[[text]] IS ''";
        $sql = $qb->dropCommentFromColumn('comment', 'text');
        $this->assertEquals($this->replaceQuotes($expected), $sql);
    }

    public function testCommentTable()
    {
        $qb = $this->getQueryBuilder();

        $expected = "COMMENT ON TABLE [[comment]] IS 'This is my table.'";
        $sql = $qb->addCommentOnTable('comment', 'This is my table.');
        $this->assertEquals($this->replaceQuotes($expected), $sql);

        $expected = "COMMENT ON TABLE [[comment]] IS ''";
        $sql = $qb->dropCommentFromTable('comment');
        $this->assertEquals($this->replaceQuotes($expected), $sql);
    }

    public function createDropIndexesProvider(): array
    {
        $result = $this->createDropIndexesProviderTrait();

        $result['drop'][0] = 'DROP INDEX [[CN_constraints_2_single]]';

        return $result;
    }

    /**
     * @dataProvider createDropIndexesProvider
     *
     * @param string $sql
     */
    public function testCreateDropIndex(string $sql, Closure $builder): void
    {
        $this->assertSame($this->getConnection()->quoteSql($sql), $builder($this->getQueryBuilder()));
    }

    /**
     * @dataProvider deleteProviderTrait
     *
     * @param string $table
     * @param array|string $condition
     * @param string $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testDelete(string $table, $condition, string $expectedSQL, array $expectedParams): void
    {
        $actualParams = [];

        $actualSQL = $this->getQueryBuilder()->delete($table, $condition, $actualParams);

        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }

    public function testResetSequence()
    {
        $db = $this->getConnection(true);
        $qb = $this->getQueryBuilder();

        $sqlResult = "SELECT last_number FROM user_sequences WHERE sequence_name = 'item_SEQ'";

        $qb->executeResetSequence('item');
        $result = $db->createCommand($sqlResult)->queryScalar();
        $this->assertEquals(6, $result);

        $qb->executeResetSequence('item', 4);
        $result = $db->createCommand($sqlResult)->queryScalar();
        $this->assertEquals(4, $result);
    }

    /**
     * @dataProvider insertProviderTrait
     *
     * @param string $table
     * @param array|ColumnSchema $columns
     * @param array $params
     * @param string $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testInsert(string $table, $columns, array $params, string $expectedSQL, array $expectedParams): void
    {
        $actualParams = $params;

        $actualSQL = $this->getQueryBuilder()->insert($table, $columns, $actualParams);

        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }

    /**
     * @dataProvider updateProviderTrait
     *
     * @param string $table
     * @param array $columns
     * @param array|string $condition
     * @param string $expectedSQL
     * @param array $expectedParams
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testUpdate(
        string $table,
        array $columns,
        $condition,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $actualParams = [];

        $actualSQL = $this->getQueryBuilder()->update($table, $columns, $condition, $actualParams);

        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }

    public function upsertProvider(): array
    {
        $concreteData = [
            'regular values' => [
                3 => 'MERGE INTO "T_upsert" USING (SELECT :qp0 AS "email", :qp1 AS "address", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"="EXCLUDED"."address", "status"="EXCLUDED"."status", "profile_id"="EXCLUDED"."profile_id" WHEN NOT MATCHED THEN INSERT ("email", "address", "status", "profile_id") VALUES ("EXCLUDED"."email", "EXCLUDED"."address", "EXCLUDED"."status", "EXCLUDED"."profile_id")',
            ],
            'regular values with update part' => [
                3 => 'MERGE INTO "T_upsert" USING (SELECT :qp0 AS "email", :qp1 AS "address", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"=:qp4, "status"=:qp5, "orders"=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ("email", "address", "status", "profile_id") VALUES ("EXCLUDED"."email", "EXCLUDED"."address", "EXCLUDED"."status", "EXCLUDED"."profile_id")',
            ],
            'regular values without update part' => [
                3 => 'MERGE INTO "T_upsert" USING (SELECT :qp0 AS "email", :qp1 AS "address", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", "address", "status", "profile_id") VALUES ("EXCLUDED"."email", "EXCLUDED"."address", "EXCLUDED"."status", "EXCLUDED"."profile_id")',
            ],
            'query' => [
                3 => 'MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0),
    PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
SELECT *
FROM PAGINATION
WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "status"="EXCLUDED"."status" WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")'
            ],
            'query with update part' => [
                3 => 'MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0),
    PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
SELECT *
FROM PAGINATION
WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"=:qp1, "status"=:qp2, "orders"=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")'
            ],
            'query without update part' => [
                3 => 'MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0),
    PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
SELECT *
FROM PAGINATION
WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")'
            ],
            'values and expressions' => [
                3 => 'INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())',
            ],
            'values and expressions with update part' => [
                3 => 'INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())',
            ],
            'values and expressions without update part' => [
                3 => 'INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())',
            ],
            'query, values and expressions with update part' => [
                3 => 'MERGE INTO {{%T_upsert}} USING (SELECT :phEmail AS "email", now() AS [[time]]) "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "ts"=:qp1, [[orders]]=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ("email", [[time]]) VALUES ("EXCLUDED"."email", "EXCLUDED".[[time]])',
            ],
            'query, values and expressions without update part' => [
                3 => 'MERGE INTO {{%T_upsert}} USING (SELECT :phEmail AS "email", now() AS [[time]]) "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "ts"=:qp1, [[orders]]=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ("email", [[time]]) VALUES ("EXCLUDED"."email", "EXCLUDED".[[time]])',
            ],
        ];

        $newData = $this->upsertProviderTrait();

        foreach ($concreteData as $testName => $data) {
            $newData[$testName] = array_replace($newData[$testName], $data);
        }

        // skip test
        unset($newData['no columns to update']);

        return $newData;
    }

    /**
     * @dataProvider upsertProvider
     *
     * @param string $table
     * @param array|ColumnSchema $insertColumns
     * @param array|bool|null $updateColumns
     * @param string|string[] $expectedSQL
     * @param array $expectedParams
     *
     * @throws NotSupportedException
     * @throws Exception
     */
    public function testUpsert(string $table, $insertColumns, $updateColumns, $expectedSQL, array $expectedParams): void
    {
        $actualParams = [];

        $actualSQL = $this->getQueryBuilder()
            ->upsert($table, $insertColumns, $updateColumns, $actualParams);

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
