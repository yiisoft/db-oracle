<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use PDO;
use Yiisoft\Db\Driver\PDO\AbstractPDODriver;

/**
 * Implements the Oracle Server driver based on the PDO (PHP Data Objects) extension.
 *
 * @see https://www.php.net/manual/en/ref.pdo-oci.php
 */
final class Driver extends AbstractPDODriver
{
    public function createConnection(): PDO
    {
        $this->attributes += [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        return parent::createConnection();
    }

    public function getDriverName(): string
    {
        return 'oci';
    }
}
