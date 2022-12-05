<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Provider\BaseQueryBuilderProvider;

use function array_replace;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryBuilderProvider
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
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        $addForeingKey = $baseQueryBuilderProvider->addForeignKey($this->getDriverName());

        $addForeingKey['add'][7] = <<<SQL
        ALTER TABLE "T_constraints_3" ADD CONSTRAINT "CN_constraints_3" FOREIGN KEY ("C_fk_id_1") REFERENCES "T_constraints_2" ("C_id_1") ON DELETE CASCADE
        SQL;
        $addForeingKey['add (2 columns)'][7] = <<<SQL
        ALTER TABLE "T_constraints_3" ADD CONSTRAINT "CN_constraints_3" FOREIGN KEY ("C_fk_id_1", "C_fk_id_2") REFERENCES "T_constraints_2" ("C_id_1", "C_id_2") ON DELETE CASCADE
        SQL;

        return $addForeingKey;
    }

    public function addPrimaryKey(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->addPrimaryKey($this->getDriverName());
    }

    public function addUnique(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->addUnique($this->getDriverName());
    }

    public function batchInsert(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        $batchInsert = $baseQueryBuilderProvider->batchInsert($this->getDriverName());

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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function buildCondition(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        $buildCondition = $baseQueryBuilderProvider->buildCondition($this->getConnection());

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

    public function buildFrom(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildFrom($this->getDriverName());
    }

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
        } catch (\Exception $e) {
            // ignore
        }


        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildLikeCondition(
            $this->getDriverName(),
            $this->likeEscapeCharSql,
            $this->likeParameterReplacements,
        );
    }

    public function buildWhereExists(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildWhereExists($this->getDriverName());
    }

    public function delete(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->delete($this->getDriverName());
    }

    public function selectExist(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        $selectExist = $baseQueryBuilderProvider->selectExist($this->getDriverName());

        $selectExist[0][1] = <<<SQL
        SELECT CASE WHEN EXISTS(SELECT 1 FROM `table` WHERE `id` = 1) THEN 1 ELSE 0 END FROM DUAL
        SQL;

        return $selectExist;
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function insert(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->insert($this->getConnection());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function insertEx(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->insertEx($this->getConnection());
    }

    public function update(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->update($this->getDriverName());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT :qp0 AS "email", :qp1 AS "address", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"="EXCLUDED"."address", "status"="EXCLUDED"."status", "profile_id"="EXCLUDED"."profile_id" WHEN NOT MATCHED THEN INSERT ("email", "address", "status", "profile_id") VALUES ("EXCLUDED"."email", "EXCLUDED"."address", "EXCLUDED"."status", "EXCLUDED"."profile_id")
                SQL,
            ],
            'regular values with update part' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT :qp0 AS "email", :qp1 AS "address", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"=:qp4, "status"=:qp5, "orders"=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ("email", "address", "status", "profile_id") VALUES ("EXCLUDED"."email", "EXCLUDED"."address", "EXCLUDED"."status", "EXCLUDED"."profile_id")
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
                /** @noRector \Rector\Php73\Rector\String_\SensitiveHereNowDocRector */
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
                SELECT * FROM PAGINATION WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"=:qp1, "status"=:qp2, "orders"=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
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
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())
                SQL,
            ],
            'values and expressions with update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())
                SQL,
            ],
            'values and expressions without update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())
                SQL,
            ],
            'query, values and expressions with update part' => [
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :phEmail AS "email", now() AS [[time]]) "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "ts"=:qp1, [[orders]]=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ("email", [[time]]) VALUES ("EXCLUDED"."email", "EXCLUDED".[[time]])
                SQL,
            ],
            'query, values and expressions without update part' => [
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :phEmail AS "email", now() AS [[time]]) "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "ts"=:qp1, [[orders]]=T_upsert.orders + 1 WHEN NOT MATCHED THEN INSERT ("email", [[time]]) VALUES ("EXCLUDED"."email", "EXCLUDED".[[time]])
                SQL,
            ],
        ];

        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        $upsert = $baseQueryBuilderProvider->upsert($this->getConnection());

        foreach ($concreteData as $testName => $data) {
            $upsert[$testName] = array_replace($upsert[$testName], $data);
        }

        // skip test
        unset($upsert['no columns to update']);

        return $upsert;
    }
}
