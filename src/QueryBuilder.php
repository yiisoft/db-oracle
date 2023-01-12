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
        SchemaInterface::TYPE_PK => 'NUMBER(10) NOT NULL PRIMARY KEY',
        SchemaInterface::TYPE_UPK => 'NUMBER(10) UNSIGNED NOT NULL PRIMARY KEY',
        SchemaInterface::TYPE_BIGPK => 'NUMBER(20) NOT NULL PRIMARY KEY',
        SchemaInterface::TYPE_UBIGPK => 'NUMBER(20) UNSIGNED NOT NULL PRIMARY KEY',
        SchemaInterface::TYPE_CHAR => 'CHAR(1)',
        SchemaInterface::TYPE_STRING => 'VARCHAR2(255)',
        SchemaInterface::TYPE_TEXT => 'CLOB',
        SchemaInterface::TYPE_TINYINT => 'NUMBER(3)',
        SchemaInterface::TYPE_SMALLINT => 'NUMBER(5)',
        SchemaInterface::TYPE_INTEGER => 'NUMBER(10)',
        SchemaInterface::TYPE_BIGINT => 'NUMBER(20)',
        SchemaInterface::TYPE_FLOAT => 'NUMBER',
        SchemaInterface::TYPE_DOUBLE => 'NUMBER',
        SchemaInterface::TYPE_DECIMAL => 'NUMBER',
        SchemaInterface::TYPE_DATETIME => 'TIMESTAMP',
        SchemaInterface::TYPE_TIMESTAMP => 'TIMESTAMP',
        SchemaInterface::TYPE_TIME => 'TIMESTAMP',
        SchemaInterface::TYPE_DATE => 'DATE',
        SchemaInterface::TYPE_BINARY => 'BLOB',
        SchemaInterface::TYPE_BOOLEAN => 'NUMBER(1)',
        SchemaInterface::TYPE_MONEY => 'NUMBER(19,4)',
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
