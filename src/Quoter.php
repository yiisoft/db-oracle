<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Schema\AbstractQuoter;

use function str_contains;

final class Quoter extends AbstractQuoter
{
    public function quoteSimpleTableName(string $name): string
    {
        return str_contains($name, '"') ? $name : '"' . $name . '"';
    }
}
