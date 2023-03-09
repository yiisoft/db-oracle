<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Schema\Quoter as BaseQuoter;

use function str_contains;

/**
 * Implements the MySQL, MariaDb Server quoting and unquoting methods.
 */
final class Quoter extends BaseQuoter
{
    public function quoteSimpleTableName(string $name): string
    {
        return str_contains($name, '"') ? $name : '"' . $name . '"';
    }
}
