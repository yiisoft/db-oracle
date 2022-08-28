<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Schema\Quoter as BaseQuoter;
use Yiisoft\Db\Schema\QuoterInterface;

use function str_contains;

final class Quoter extends BaseQuoter
{
    public function quoteSimpleTableName(string $name): string
    {
        return str_contains($name, '"') ? $name : '"' . $name . '"';
    }
}
