<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Provider\AbstractQueryBuilderProvider;

use function array_replace;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryBuilderProvider extends AbstractQueryBuilderProvider
{
    use TestTrait;

    protected string $likeEscapeCharSql = " ESCAPE '!'";
    protected array $likeParameterReplacements = [
        '\%' => '!%',
        '\_' => '!_',
        '!' => '!!',
    ];

    public function addForeignKey(): array
    {
        $addForeingKey = parent::addForeignKey();

        $addForeingKey['add'][7] = <<<SQL
        ALTER TABLE "T_constraints_3" ADD CONSTRAINT "CN_constraints_3" FOREIGN KEY ("C_fk_id_1") REFERENCES "T_constraints_2" ("C_id_1") ON DELETE CASCADE
        SQL;
        $addForeingKey['add (2 columns)'][7] = <<<SQL
        ALTER TABLE "T_constraints_3" ADD CONSTRAINT "CN_constraints_3" FOREIGN KEY ("C_fk_id_1", "C_fk_id_2") REFERENCES "T_constraints_2" ("C_id_1", "C_id_2") ON DELETE CASCADE
        SQL;

        return $addForeingKey;
    }

    public function batchInsert(): array
    {
        $batchInsert = parent::batchInsert();

        $this->changeSqlForOracleBatchInsert($batchInsert['simple']['expected']) ;
        $this->changeSqlForOracleBatchInsert($batchInsert['escape-danger-chars']['expected']) ;
        $this->changeSqlForOracleBatchInsert($batchInsert['customer3']['expected']) ;
        $this->changeSqlForOracleBatchInsert($batchInsert['bool-false, bool2-null']['expected']);

        $batchInsert['wrong']['expected'] = <<<SQL
        INSERT ALL  INTO {{%type}} ({{%type}}.[[float_col]], [[time]]) VALUES (:qp0, now()) INTO {{%type}} ({{%type}}.[[float_col]], [[time]]) VALUES (:qp1, now()) SELECT 1 FROM SYS.DUAL
        SQL;

        $this->changeSqlForOracleBatchInsert($batchInsert['bool-false, time-now()']['expected']);

        return $batchInsert;
    }

    public function buildCondition(): array
    {
        $buildCondition = parent::buildCondition();

        $buildCondition['like-custom-1'] = [['like', 'a', 'b'], '"a" LIKE :qp0 ESCAPE \'!\'', [':qp0' => '%b%']];
        $buildCondition['like-custom-2'] = [
            ['like', 'a', new Expression(':qp0', [':qp0' => '%b%'])],
            '"a" LIKE :qp0 ESCAPE \'!\'',
            [':qp0' => '%b%'],
        ];
        $buildCondition['like-custom-3'] = [
            ['like', new Expression('CONCAT(col1, col2)'), 'b'], 'CONCAT(col1, col2) LIKE :qp0 ESCAPE \'!\'', [':qp0' => '%b%'],
        ];

        return $buildCondition;
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function buildLikeCondition(): array
    {
        $db = $this->getConnection();

        /*
         * Different pdo_oci8 versions may or may not implement PDO::quote(), so \Yiisoft\Db\Oracle\Quoter::quoteValue()
         * may or may not quote \.
         */
        try {
            $encodedBackslash = substr($db->getQuoter()->quoteValue('\\\\'), 1, -1);

            $this->likeParameterReplacements[$encodedBackslash] = '\\';
        } catch (\Exception) {
            // ignore
        }


        return parent::buildLikeCondition();
    }

    public function selectExist(): array
    {
        $selectExist = parent::selectExist();

        $selectExist[0][1] = <<<SQL
        SELECT CASE WHEN EXISTS(SELECT 1 FROM `table` WHERE `id` = 1) THEN 1 ELSE 0 END FROM DUAL
        SQL;

        return $selectExist;
    }

    public function upsert(): array
    {
        $db = $this->getConnection();

        $concreteData = [
            'regular values' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT :qp0 AS "email", :qp1 AS "address", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"="EXCLUDED"."address", "status"="EXCLUDED"."status", "profile_id"="EXCLUDED"."profile_id" WHEN NOT MATCHED THEN INSERT ("email", "address", "status", "profile_id") VALUES ("EXCLUDED"."email", "EXCLUDED"."address", "EXCLUDED"."status", "EXCLUDED"."profile_id")
                SQL,
            ],
            'regular values with update part' => [
                2 => ['address' => 'foo {{city}}', 'status' => 2, 'orders' => new Expression('"T_upsert"."orders" + 1')],
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT :qp0 AS "email", :qp1 AS "address", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"=:qp4, "status"=:qp5, "orders"="T_upsert"."orders" + 1 WHEN NOT MATCHED THEN INSERT ("email", "address", "status", "profile_id") VALUES ("EXCLUDED"."email", "EXCLUDED"."address", "EXCLUDED"."status", "EXCLUDED"."profile_id")
                SQL,
            ],
            'regular values without update part' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT :qp0 AS "email", :qp1 AS "address", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", "address", "status", "profile_id") VALUES ("EXCLUDED"."email", "EXCLUDED"."address", "EXCLUDED"."status", "EXCLUDED"."profile_id")
                SQL,
            ],
            'query' => [
                /** @noRector \Rector\Php73\Rector\String_\SensitiveHereNowDocRector */
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
                SELECT * FROM PAGINATION WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "status"="EXCLUDED"."status" WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
                SQL,
            ],
            'query with update part' => [
                2 => ['address' => 'foo {{city}}', 'status' => 2, 'orders' => new Expression('"T_upsert"."orders" + 1')],
                /** @noRector \Rector\Php73\Rector\String_\SensitiveHereNowDocRector */
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
                SELECT * FROM PAGINATION WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"=:qp1, "status"=:qp2, "orders"="T_upsert"."orders" + 1 WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
                SQL,
            ],
            'query without update part' => [
                /** @noRector \Rector\Php73\Rector\String_\SensitiveHereNowDocRector */
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
                SELECT * FROM PAGINATION WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
                SQL,
            ],
            'values and expressions' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('ROUND((SYSDATE - DATE \'1970-01-01\')*24*60*60)')],
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :qp0 AS "email", ROUND((SYSDATE - DATE '1970-01-01')*24*60*60) AS [[ts]] FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET [[ts]]="EXCLUDED".[[ts]] WHEN NOT MATCHED THEN INSERT ("email", [[ts]]) VALUES ("EXCLUDED"."email", "EXCLUDED".[[ts]])
                SQL,
            ],
            'values and expressions with update part' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('ROUND((SYSDATE - DATE \'1970-01-01\')*24*60*60)')],
                2 => ['[[orders]]' => new Expression('"T_upsert"."orders" + 1')],
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :qp0 AS "email", ROUND((SYSDATE - DATE '1970-01-01')*24*60*60) AS [[ts]] FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET [[orders]]="T_upsert"."orders" + 1 WHEN NOT MATCHED THEN INSERT ("email", [[ts]]) VALUES ("EXCLUDED"."email", "EXCLUDED".[[ts]])
                SQL,
            ],
            'values and expressions without update part' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('ROUND((SYSDATE - DATE \'1970-01-01\')*24*60*60)')],
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :qp0 AS "email", ROUND((SYSDATE - DATE '1970-01-01')*24*60*60) AS [[ts]] FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", [[ts]]) VALUES ("EXCLUDED"."email", "EXCLUDED".[[ts]])
                SQL,
            ],
            'query, values and expressions with update part' => [
                1 => (new Query($db))
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[ts]]' => new Expression('ROUND((SYSDATE - DATE \'1970-01-01\')*24*60*60)'),
                        ],
                    )->from('DUAL'),
                2 => ['ts' => 0, '[[orders]]' => new Expression('"T_upsert"."orders" + 1')],
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :phEmail AS "email", ROUND((SYSDATE - DATE '1970-01-01')*24*60*60) AS [[ts]] FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "ts"=:qp1, [[orders]]="T_upsert"."orders" + 1 WHEN NOT MATCHED THEN INSERT ("email", [[ts]]) VALUES ("EXCLUDED"."email", "EXCLUDED".[[ts]])
                SQL,
            ],
            'query, values and expressions without update part' => [
                1 => (new Query($db))
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[ts]]' => new Expression('ROUND((SYSDATE - DATE \'1970-01-01\')*24*60*60)'),
                        ],
                    )->from('DUAL'),
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :phEmail AS "email", ROUND((SYSDATE - DATE '1970-01-01')*24*60*60) AS [[ts]] FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", [[ts]]) VALUES ("EXCLUDED"."email", "EXCLUDED".[[ts]])
                SQL,
            ],
            'no columns to update' => [
                3 => <<<SQL
                MERGE INTO "T_upsert_1" USING (SELECT :qp0 AS "a" FROM "DUAL") "EXCLUDED" ON ("T_upsert_1"."a"="EXCLUDED"."a") WHEN NOT MATCHED THEN INSERT ("a") VALUES ("EXCLUDED"."a")
                SQL,
            ],
            'no columns to update with unique' => [
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :qp0 AS "email" FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email") VALUES ("EXCLUDED"."email")
                SQL,
            ],
            'no unique columns in table - simple insert' => [
                3 => 'INSERT INTO {{%animal}} ("type") VALUES (:qp0)'
            ],
        ];

        $upsert = parent::upsert();

        foreach ($concreteData as $testName => $data) {
            $upsert[$testName] = array_replace($upsert[$testName], $data);
        }

        return $upsert;
    }
}
