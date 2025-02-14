<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Connection\ServerInfoInterface;
use Yiisoft\Db\Oracle\Column\ColumnDefinitionBuilder;
use Yiisoft\Db\QueryBuilder\AbstractQueryBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function bin2hex;

/**
 * Implements the Oracle Server specific query builder.
 */
final class QueryBuilder extends AbstractQueryBuilder
{
    public function __construct(QuoterInterface $quoter, SchemaInterface $schema, ServerInfoInterface $serverInfo)
    {
        parent::__construct(
            $quoter,
            $schema,
            $serverInfo,
            new DDLQueryBuilder($this, $quoter, $schema),
            new DMLQueryBuilder($this, $quoter, $schema),
            new DQLQueryBuilder($this, $quoter),
            new ColumnDefinitionBuilder($this),
        );
    }

    protected function prepareBinary(string $binary): string
    {
        return "HEXTORAW('" . bin2hex($binary) . "')";
    }
}
