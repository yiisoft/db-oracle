<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Oracle\Tests\TestCase;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\TestSupport\Provider\QueryBuilderProvider as BaseQueryBuilderProvider;

final class QueryBuilderProvider extends TestCase
{
    protected string $likeEscapeCharSql = " ESCAPE '!'";
    protected array $likeParameterReplacements = [
        '\%' => '!%',
        '\_' => '!_',
        '!' => '!!',
    ];

    public function addDropChecksProvider(): array
    {
        $tableName = 'T_constraints_1';
        $name = 'CN_check';

        return [
            'drop' => [
                "ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]",
                static function (QueryBuilderInterface $qb) use ($tableName, $name) {
                    return $qb->dropCheck($name, $tableName);
                },
            ],
            'add' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] CHECK ([[C_not_null]] > 100)",
                static function (QueryBuilderInterface $qb) use ($tableName, $name) {
                    return $qb->addCheck($name, $tableName, '[[C_not_null]] > 100');
                },
            ],
        ];
    }

    public function addDropForeignKeysProvider()
    {
        $tableName = 'T_constraints_3';
        $name = 'CN_constraints_3';
        $pkTableName = 'T_constraints_2';
        return [
            'drop' => [
                "ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]",
                function (QueryBuilderInterface $qb) use ($tableName, $name) {
                    return $qb->dropForeignKey($name, $tableName);
                },
            ],
            'add' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]]) REFERENCES {{{$pkTableName}}} ([[C_id_1]]) ON DELETE CASCADE",
                function (QueryBuilderInterface $qb) use ($tableName, $name, $pkTableName) {
                    return $qb->addForeignKey($name, $tableName, 'C_fk_id_1', $pkTableName, 'C_id_1', 'CASCADE');
                },
            ],
            'add (2 columns)' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]], [[C_fk_id_2]]) REFERENCES {{{$pkTableName}}} ([[C_id_1]], [[C_id_2]]) ON DELETE CASCADE",
                function (QueryBuilderInterface $qb) use ($tableName, $name, $pkTableName) {
                    return $qb->addForeignKey($name, $tableName, 'C_fk_id_1, C_fk_id_2', $pkTableName, 'C_id_1, C_id_2', 'CASCADE');
                },
            ],
        ];
    }

    public function addDropPrimaryKeysProvider(): array
    {
        return (new BaseQueryBuilderProvider($this->getConnection()))->addDropPrimaryKeysProvider();
    }

    public function addDropUniquesProvider(): array
    {
        return (new BaseQueryBuilderProvider($this->getConnection()))->addDropUniquesProvider();
    }

    public function batchInsertProvider(): array
    {
        $data = (new BaseQueryBuilderProvider($this->getConnection()))->batchInsertProvider();

        $this->changeSqlForOracleBatchInsert($data['simple']['expected']) ;
        $this->changeSqlForOracleBatchInsert($data['escape-danger-chars']['expected']) ;
        $this->changeSqlForOracleBatchInsert($data['customer3']['expected']) ;
        $this->changeSqlForOracleBatchInsert($data['bool-false, bool2-null']['expected']);

        $data['wrong']['expected'] = 'INSERT ALL  INTO {{%type}} ({{%type}}.[[float_col]], [[time]]) ' .
            'VALUES (:qp0, now()) INTO {{%type}} ({{%type}}.[[float_col]], [[time]]) VALUES (:qp1, now()) SELECT 1 FROM SYS.DUAL';

        $this->changeSqlForOracleBatchInsert($data['bool-false, time-now()']['expected']);

        return $data;
    }

    public function buildConditionsProvider(): array
    {
        return (new BaseQueryBuilderProvider($this->getConnection()))->buildConditionsProvider();
    }

    public function buildExistsParamsProvider(): array
    {
        return (new BaseQueryBuilderProvider($this->getConnection()))->buildExistsParamsProvider();
    }

    public function buildFilterConditionProvider(): array
    {
        return (new BaseQueryBuilderProvider($this->getConnection()))->buildFilterConditionProvider();
    }

    public function buildFromDataProvider(): array
    {
        return (new BaseQueryBuilderProvider($this->getConnection()))->buildFromDataProvider();
    }

    public function buildLikeConditionsProvider(): array
    {
        $db = $this->getConnection();
        /*
         * Different pdo_oci8 versions may or may not implement PDO::quote(), so
         * \Yiisoft\Db\Schema\Schema::quoteValue() may or may not quote \.
         */
        try {
            $encodedBackslash = substr($db->getQuoter()->quoteValue('\\\\'), 1, -1);
            $this->likeParameterReplacements[$encodedBackslash] = '\\';
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not execute Connection::quoteValue() method: ' . $e->getMessage());
        }

        return (new BaseQueryBuilderProvider(
            $this->getConnection(),
            $this->likeEscapeCharSql,
            $this->likeParameterReplacements
        ))->buildLikeConditionsProvider();
    }

    public function createDropIndexesProvider(): array
    {
        $result = (new BaseQueryBuilderProvider($this->getConnection()))->createDropIndexesProvider();
        $result['drop'][0] = 'DROP INDEX [[CN_constraints_2_single]]';
        return $result;
    }

    public function deleteProvider(): array
    {
        return (new BaseQueryBuilderProvider($this->getConnection()))->deleteProvider();
    }

    public function insertProvider(): array
    {
        return [
            'regular-values' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'silverfire',
                    'address' => 'Kyiv {{city}}, Ukraine',
                    'is_active' => false,
                    'related_id' => null,
                ],
                [],
                $this->replaceQuotes(
                    'INSERT INTO [[customer]] ([[email]], [[name]], [[address]], [[is_active]], [[related_id]])'
                    . ' VALUES (:qp0, :qp1, :qp2, :qp3, :qp4)'
                ),
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'silverfire',
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => false,
                    ':qp4' => null,
                ],
            ],
            'params-and-expressions' => [
                '{{%type}}',
                [
                    '{{%type}}.[[related_id]]' => null,
                    '[[time]]' => new Expression('now()'),
                ],
                [],
                'INSERT INTO {{%type}} ({{%type}}.[[related_id]], [[time]]) VALUES (:qp0, now())',
                [
                    ':qp0' => null,
                ],
            ],
            'carry passed params' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'sergeymakinen',
                    'address' => '{{city}}',
                    'is_active' => false,
                    'related_id' => null,
                    'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                ],
                [':phBar' => 'bar'],
                $this->replaceQuotes(
                    'INSERT INTO [[customer]] ([[email]], [[name]], [[address]], [[is_active]], [[related_id]], [[col]])'
                    . ' VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar))'
                ),
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':qp5' => null,
                    ':phFoo' => 'foo',
                ],
            ],
            'carry passed params (query)' => [
                'customer',
                (new Query($this->getConnection()))
                    ->select([
                        'email',
                        'name',
                        'address',
                        'is_active',
                        'related_id',
                    ])
                    ->from('customer')
                    ->where([
                        'email' => 'test@example.com',
                        'name' => 'sergeymakinen',
                        'address' => '{{city}}',
                        'is_active' => false,
                        'related_id' => null,
                        'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                    ]),
                [':phBar' => 'bar'],
                $this->replaceQuotes(
                    'INSERT INTO [[customer]] ([[email]], [[name]], [[address]], [[is_active]], [[related_id]])'
                    . ' SELECT [[email]], [[name]], [[address]], [[is_active]], [[related_id]] FROM [[customer]]'
                    . ' WHERE ([[email]]=:qp1) AND ([[name]]=:qp2) AND ([[address]]=:qp3) AND ([[is_active]]=:qp4)'
                    . ' AND ([[related_id]] IS NULL) AND ([[col]]=CONCAT(:phFoo, :phBar))'
                ),
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':phFoo' => 'foo',
                ],
            ],
        ];
    }

    public function updateProvider(): array
    {
        return [
            [
                'customer',
                [
                    'status' => 1,
                    'updated_at' => new Expression('now()'),
                ],
                [
                    'id' => 100,
                ],
                $this->replaceQuotes(
                    'UPDATE [[customer]] SET [[status]]=:qp0, [[updated_at]]=now() WHERE [[id]]=:qp1'
                ),
                [
                    ':qp0' => 1,
                    ':qp1' => 100,
                ],
            ],
        ];
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
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
                SELECT * FROM PAGINATION WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "status"="EXCLUDED"."status" WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
                SQL,
            ],
            'query with update part' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
                SELECT * FROM PAGINATION WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"=:qp1, "status"=:qp2, "orders"=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
                SQL,
            ],
            'query without update part' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
                SELECT * FROM PAGINATION WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
                SQL,
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

        $newData = (new BaseQueryBuilderProvider($this->getConnection()))->upsertProvider();

        foreach ($concreteData as $testName => $data) {
            $newData[$testName] = array_replace($newData[$testName], $data);
        }

        // skip test
        unset($newData['no columns to update']);

        return $newData;
    }
}
