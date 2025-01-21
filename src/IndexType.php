<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

/**
 * Defines the available index types for {@see DDLQueryBuilder::createIndex()} method.
 */
final class IndexType
{
    /**
     * Define the type of the index as `UNIQUE`.
     */
    public const UNIQUE = 'UNIQUE';
    /**
     * Define the type of the index as `BITMAP`.
     */
    public const BITMAP = 'BITMAP';
    /**
     * Define the type of the index as `MULTIVALUE`.
     */
    public const MULTIVALUE = 'MULTIVALUE';
    /**
     * Define the type of the index as `SEARCH`.
     */
    public const SEARCH = 'SEARCH';
}
