<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Driver\PDO\AbstractTransactionPDO;

/**
 * Implements the Oracle Server specific transaction.
 */
final class TransactionPDO extends AbstractTransactionPDO
{
    public function releaseSavepoint(string $name): void
    {
        // does nothing as Oracle doesn't support this.
    }
}
