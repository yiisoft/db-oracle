<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

/**
 * Implements the Oracle Server specific transaction.
 */
final class Transaction extends \Yiisoft\Db\Driver\Pdo\AbstractTransaction
{
    public function releaseSavepoint(string $name): void
    {
        // does nothing as Oracle doesn't support this.
    }
}
