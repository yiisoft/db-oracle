<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Driver\PDO\AbstractTransactionPDO;

final class TransactionPDO extends AbstractTransactionPDO
{
    public function releaseSavepoint(string $name): void
    {
        // does nothing as Oracle does not support this
    }
}
