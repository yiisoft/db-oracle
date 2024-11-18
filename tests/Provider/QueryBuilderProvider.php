<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use Exception;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Oracle\Column\ColumnBuilder;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Support\DbHelper;

use function array_replace;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryBuilderProvider extends \Yiisoft\Db\Tests\Provider\QueryBuilderProvider
{
    use TestTrait;

    protected static string $driverName = 'oci';
    protected static string $likeEscapeCharSql = " ESCAPE '!'";
    protected static array $likeParameterReplacements = [
        '\%' => '!%',
        '\_' => '!_',
        '!' => '!!',
    ];

    public static function addForeignKey(): array
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

    public static function batchInsert(): array
    {
        $batchInsert = parent::batchInsert();

        foreach ($batchInsert as $key => $value) {
            DbHelper::changeSqlForOracleBatchInsert($batchInsert[$key]['expected']);
        }

        $batchInsert['bool-false, bool2-null']['expectedParams'][':qp0'] = '0';
        $batchInsert['bool-false, time-now()']['expectedParams'][':qp0'] = '0';
        $batchInsert['column table names are not checked']['expectedParams'] = [
            ':qp0' => '1',
            ':qp1' => '0',
        ];

        return $batchInsert;
    }

    public static function buildCondition(): array
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

    public static function buildLikeCondition(): array
    {
        /*
         * Different pdo_oci8 versions may or may not implement PDO::quote(), so \Yiisoft\Db\Oracle\Quoter::quoteValue()
         * may or may not quote \.
         */
        try {
            $encodedBackslash = substr(self::getDb()->quoteValue('\\\\'), 1, -1);

            self::$likeParameterReplacements[$encodedBackslash] = '\\';
        } catch (Exception) {
            // ignore
        }


        return parent::buildLikeCondition();
    }

    public static function insert(): array
    {
        $insert = parent::insert();

        $insert['empty columns'][3] = <<<SQL
        INSERT INTO "customer" ("id") VALUES (DEFAULT)
        SQL;

        return $insert;
    }

    public static function selectExist(): array
    {
        $selectExist = parent::selectExist();

        $selectExist[0][1] = <<<SQL
        SELECT CASE WHEN EXISTS(SELECT 1 FROM `table` WHERE `id` = 1) THEN 1 ELSE 0 END FROM DUAL
        SQL;

        return $selectExist;
    }

    public static function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT :qp0 AS "email", :qp1 AS "address", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"="EXCLUDED"."address", "status"="EXCLUDED"."status", "profile_id"="EXCLUDED"."profile_id" WHEN NOT MATCHED THEN INSERT ("email", "address", "status", "profile_id") VALUES ("EXCLUDED"."email", "EXCLUDED"."address", "EXCLUDED"."status", "EXCLUDED"."profile_id")
                SQL,
            ],
            'regular values with unique at not the first position' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (SELECT :qp0 AS "address", :qp1 AS "email", :qp2 AS "status", :qp3 AS "profile_id" FROM "DUAL") "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"="EXCLUDED"."address", "status"="EXCLUDED"."status", "profile_id"="EXCLUDED"."profile_id" WHEN NOT MATCHED THEN INSERT ("address", "email", "status", "profile_id") VALUES ("EXCLUDED"."address", "EXCLUDED"."email", "EXCLUDED"."status", "EXCLUDED"."profile_id")
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
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
                SELECT * FROM PAGINATION WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "status"="EXCLUDED"."status" WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
                SQL,
            ],
            'query with update part' => [
                2 => ['address' => 'foo {{city}}', 'status' => 2, 'orders' => new Expression('"T_upsert"."orders" + 1')],
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
                SELECT * FROM PAGINATION WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "address"=:qp1, "status"=:qp2, "orders"="T_upsert"."orders" + 1 WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
                SQL,
            ],
            'query without update part' => [
                3 => <<<SQL
                MERGE INTO "T_upsert" USING (WITH USER_SQL AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0), PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
                SELECT * FROM PAGINATION WHERE rownum <= 1) "EXCLUDED" ON ("T_upsert"."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", "status") VALUES ("EXCLUDED"."email", "EXCLUDED"."status")
                SQL,
            ],
            'values and expressions' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('ROUND((SYSDATE - DATE \'1970-01-01\')*24*60*60)')],
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :qp0 AS "email", ROUND((SYSDATE - DATE '1970-01-01')*24*60*60) AS "ts" FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "ts"="EXCLUDED"."ts" WHEN NOT MATCHED THEN INSERT ("email", "ts") VALUES ("EXCLUDED"."email", "EXCLUDED"."ts")
                SQL,
            ],
            'values and expressions with update part' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('ROUND((SYSDATE - DATE \'1970-01-01\')*24*60*60)')],
                2 => ['[[orders]]' => new Expression('"T_upsert"."orders" + 1')],
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :qp0 AS "email", ROUND((SYSDATE - DATE '1970-01-01')*24*60*60) AS "ts" FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "orders"="T_upsert"."orders" + 1 WHEN NOT MATCHED THEN INSERT ("email", "ts") VALUES ("EXCLUDED"."email", "EXCLUDED"."ts")
                SQL,
            ],
            'values and expressions without update part' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('ROUND((SYSDATE - DATE \'1970-01-01\')*24*60*60)')],
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :qp0 AS "email", ROUND((SYSDATE - DATE '1970-01-01')*24*60*60) AS "ts" FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN NOT MATCHED THEN INSERT ("email", "ts") VALUES ("EXCLUDED"."email", "EXCLUDED"."ts")
                SQL,
            ],
            'query, values and expressions with update part' => [
                1 => (new Query(self::getDb()))
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[ts]]' => new Expression('ROUND((SYSDATE - DATE \'1970-01-01\')*24*60*60)'),
                        ],
                    )->from('DUAL'),
                2 => ['ts' => 0, '[[orders]]' => new Expression('"T_upsert"."orders" + 1')],
                3 => <<<SQL
                MERGE INTO {{%T_upsert}} USING (SELECT :phEmail AS "email", ROUND((SYSDATE - DATE '1970-01-01')*24*60*60) AS [[ts]] FROM "DUAL") "EXCLUDED" ON ({{%T_upsert}}."email"="EXCLUDED"."email") WHEN MATCHED THEN UPDATE SET "ts"=:qp1, "orders"="T_upsert"."orders" + 1 WHEN NOT MATCHED THEN INSERT ("email", [[ts]]) VALUES ("EXCLUDED"."email", "EXCLUDED".[[ts]])
                SQL,
            ],
            'query, values and expressions without update part' => [
                1 => (new Query(self::getDb()))
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
                3 => 'INSERT INTO {{%animal}} ("type") VALUES (:qp0)',
            ],
        ];

        $upsert = parent::upsert();

        foreach ($concreteData as $testName => $data) {
            $upsert[$testName] = array_replace($upsert[$testName], $data);
        }

        return $upsert;
    }

    public static function buildColumnDefinition(): array
    {
        $referenceRestrict = new ForeignKeyConstraint();
        $referenceRestrict->foreignColumnNames(['id']);
        $referenceRestrict->foreignTableName('ref_table');
        $referenceRestrict->onDelete('restrict');

        $referenceSetNull = clone $referenceRestrict;
        $referenceSetNull->onDelete('set null');

        $values = parent::buildColumnDefinition();

        $values[PseudoType::PK][0] = 'number(10) GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY';
        $values[PseudoType::UPK][0] = 'number(10) GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY';
        $values[PseudoType::BIGPK][0] = 'number(20) GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY';
        $values[PseudoType::UBIGPK][0] = 'number(20) GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY';
        $values[PseudoType::UUID_PK][0] = 'raw(16) DEFAULT sys_guid() PRIMARY KEY';
        $values[PseudoType::UUID_PK_SEQ][0] = 'raw(16) DEFAULT sys_guid() PRIMARY KEY';
        $values['STRING'][0] = 'varchar2(255)';
        $values['STRING(100)'][0] = 'varchar2(100)';
        $values['primaryKey()'][0] = 'number(10) GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY';
        $values['primaryKey(false)'][0] = 'number(10) PRIMARY KEY';
        $values['smallPrimaryKey()'][0] = 'number(5) GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY';
        $values['smallPrimaryKey(false)'][0] = 'number(5) PRIMARY KEY';
        $values['bigPrimaryKey()'][0] = 'number(20) GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY';
        $values['bigPrimaryKey(false)'][0] = 'number(20) PRIMARY KEY';
        $values['uuidPrimaryKey()'][0] = 'raw(16) DEFAULT sys_guid() PRIMARY KEY';
        $values['uuidPrimaryKey(false)'][0] = 'raw(16) PRIMARY KEY';
        $values['boolean()'][0] = 'number(1)';
        $values['boolean(100)'][0] = 'number(1)';
        $values['bit()'][0] = 'number(38)';
        $values['bit(1)'][0] = 'number(1)';
        $values['bit(8)'][0] = 'number(3)';
        $values['bit(64)'][0] = 'number(20)';
        $values['tinyint()'][0] = 'number(3)';
        $values['tinyint(2)'][0] = 'number(2)';
        $values['smallint()'][0] = 'number(5)';
        $values['smallint(4)'][0] = 'number(4)';
        $values['integer()'][0] = 'number(10)';
        $values['integer(8)'][0] = 'number(8)';
        $values['bigint()'][0] = 'number(20)';
        $values['bigint(15)'][0] = 'number(15)';
        $values['float()'][0] = 'binary_float';
        $values['float(10)'][0] = 'binary_float';
        $values['float(10,2)'][0] = 'binary_float';
        $values['double()'][0] = 'binary_double';
        $values['double(10)'][0] = 'binary_double';
        $values['double(10,2)'][0] = 'binary_double';
        $values['decimal()'][0] = 'number(10,0)';
        $values['decimal(5)'][0] = 'number(5,0)';
        $values['decimal(5,2)'][0] = 'number(5,2)';
        $values['decimal(null)'][0] = 'number(10,0)';
        $values['money()'][0] = 'number(19,4)';
        $values['money(10)'][0] = 'number(10,4)';
        $values['money(10,2)'][0] = 'number(10,2)';
        $values['money(null)'][0] = 'number(19,4)';
        $values['string()'][0] = 'varchar2(255)';
        $values['string(100)'][0] = 'varchar2(100)';
        $values['string(null)'][0] = 'varchar2(255)';
        $values['text()'][0] = 'clob';
        $values['text(1000)'][0] = 'clob';
        $values['binary()'][0] = 'blob';
        $values['binary(1000)'][0] = 'blob';
        $values['uuid()'][0] = 'raw(16)';
        $values['datetime()'][0] = 'timestamp(0)';
        $values['datetime(6)'][0] = 'timestamp(6)';
        $values['datetime(null)'][0] = 'timestamp';
        $values['time()'][0] = 'interval day(0) to second(0)';
        $values['time(6)'][0] = 'interval day(0) to second(6)';
        $values['time(null)'][0] = 'interval day(0) to second';
        $values['array()'][0] = 'clob';
        $values['structured()'][0] = 'clob';
        $values["structured('json')"] = ['blob', ColumnBuilder::structured('blob')];
        $values['json()'][0] = 'clob';
        $values['json(100)'][0] = 'clob';
        $values["extra('NOT NULL')"][0] = 'varchar2(255) NOT NULL';
        $values["extra('')"][0] = 'varchar2(255)';
        $values["check('value > 5')"][0] = 'number(10) CHECK ("col_59" > 5)';
        $values["check('')"][0] = 'number(10)';
        $values['check(null)'][0] = 'number(10)';
        $values["comment('comment')"][0] = 'varchar2(255)';
        $values["comment('')"][0] = 'varchar2(255)';
        $values['comment(null)'][0] = 'varchar2(255)';
        $values["defaultValue('value')"][0] = "varchar2(255) DEFAULT 'value'";
        $values["defaultValue('')"][0] = "varchar2(255) DEFAULT ''";
        $values['defaultValue(null)'][0] = 'varchar2(255) DEFAULT NULL';
        $values['defaultValue($expression)'][0] = 'number(10) DEFAULT (1 + 2)';
        $values['notNull()->defaultValue(null)'][0] = 'varchar2(255) NOT NULL';
        $values['notNull()'][0] = 'varchar2(255) NOT NULL';
        $values['null()'][0] = 'varchar2(255) NULL';
        $values['integer()->primaryKey()'][0] = 'number(10) PRIMARY KEY';
        $values["integer()->defaultValue('')"][0] = 'number(10) DEFAULT NULL';
        $values['size(10)'][0] = 'varchar2(10)';
        $values['unique()'][0] = 'varchar2(255) UNIQUE';
        $values['unsigned()'][0] = 'number(10)';
        $values['scale(2)'][0] = 'number(10,2)';
        $values['integer(8)->scale(2)'][0] = 'number(8)';
        $values['reference($reference)'][0] = 'number(10) REFERENCES "ref_table" ("id") ON DELETE CASCADE';
        $values['reference($referenceWithSchema)'][0] = 'number(10) REFERENCES "ref_schema"."ref_table" ("id") ON DELETE CASCADE';

        return [
            ...$values,

            ['number(10) REFERENCES "ref_table" ("id")', ColumnBuilder::integer()->reference($referenceRestrict)],
            ['number(10) REFERENCES "ref_table" ("id") ON DELETE SET NULL', ColumnBuilder::integer()->reference($referenceSetNull)],
        ];
    }
}
