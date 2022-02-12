<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Query\DDLQueryBuilder as AbstractDDLQueryBuilder;
use Yiisoft\Db\Query\QueryBuilderInterface;

final class DDLQueryBuilder extends AbstractDDLQueryBuilder
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
        parent::__construct($queryBuilder);
    }

    public function dropCommentFromColumn(string $table, string $column): string
    {
        return 'COMMENT ON COLUMN '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . '.'
            . $this->queryBuilder->quoter()->quoteColumnName($column)
            . " IS ''";
    }

    public function dropCommentFromTable(string $table): string
    {
        return 'COMMENT ON TABLE ' . $this->queryBuilder->quoter()->quoteTableName($table) . " IS ''";
    }
}
