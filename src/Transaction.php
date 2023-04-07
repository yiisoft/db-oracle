<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Driver\Pdo\AbstractPdoTransaction;

/**
 * Implements the Oracle Server specific transaction.
 */
final class Transaction extends AbstractPdoTransaction
{
    public function releaseSavepoint(string $name): void
    {
        // does nothing as Oracle doesn't support this.
    }
}
