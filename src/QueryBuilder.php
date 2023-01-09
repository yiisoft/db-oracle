<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\QueryBuilder\AbstractQueryBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

/**
 * QueryBuilder is the query builder for Oracle databases.
 */
final class QueryBuilder extends AbstractQueryBuilder
{
    /**
     * Defines a BITMAP index type for {@see createIndex()}.
     */
    public const INDEX_BITMAP = 'BITMAP';

    /**
     * @psalm-var string[] $typeMap Mapping from abstract column types (keys) to physical column types (values).
     */
    protected array $typeMap = [
        Schema::TYPE_PK => 'NUMBER(10) NOT NULL PRIMARY KEY',
        Schema::TYPE_UPK => 'NUMBER(10) UNSIGNED NOT NULL PRIMARY KEY',
        Schema::TYPE_BIGPK => 'NUMBER(20) NOT NULL PRIMARY KEY',
        Schema::TYPE_UBIGPK => 'NUMBER(20) UNSIGNED NOT NULL PRIMARY KEY',
        Schema::TYPE_CHAR => 'CHAR(1)',
        Schema::TYPE_STRING => 'VARCHAR2(255)',
        Schema::TYPE_TEXT => 'CLOB',
        Schema::TYPE_TINYINT => 'NUMBER(3)',
        Schema::TYPE_SMALLINT => 'NUMBER(5)',
        Schema::TYPE_INTEGER => 'NUMBER(10)',
        Schema::TYPE_BIGINT => 'NUMBER(20)',
        Schema::TYPE_FLOAT => 'NUMBER',
        Schema::TYPE_DOUBLE => 'NUMBER',
        Schema::TYPE_DECIMAL => 'NUMBER',
        Schema::TYPE_DATETIME => 'TIMESTAMP',
        Schema::TYPE_TIMESTAMP => 'TIMESTAMP',
        Schema::TYPE_TIME => 'TIMESTAMP',
        Schema::TYPE_DATE => 'DATE',
        Schema::TYPE_BINARY => 'BLOB',
        Schema::TYPE_BOOLEAN => 'NUMBER(1)',
        Schema::TYPE_MONEY => 'NUMBER(19,4)',
    ];
    private DDLQueryBuilder $ddlBuilder;
    private DMLQueryBuilder $dmlBuilder;
    private DQLQueryBuilder $dqlBuilder;

    public function __construct(QuoterInterface $quoter, SchemaInterface $schema)
    {
        $this->ddlBuilder = new DDLQueryBuilder($this, $quoter, $schema);
        $this->dmlBuilder = new DMLQueryBuilder($this, $quoter, $schema);
        $this->dqlBuilder = new DQLQueryBuilder($this, $quoter, $schema);
        parent::__construct($quoter, $schema, $this->ddlBuilder, $this->dmlBuilder, $this->dqlBuilder);
    }
}
