<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\PDO;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Transaction\TransactionPDO;

final class TransactionPDOOracle extends TransactionPDO
{
    public function releaseSavepoint(string $name): void
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }
}
