<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\PDO;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Transaction\TransactionPDO;

final class TransactionPDOOracle extends TransactionPDO
{
    public function releaseSavepoint(string $name): void
    {
        // does nothing as Oracle does not support this
    }
}
