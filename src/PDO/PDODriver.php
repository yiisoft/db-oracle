<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\PDO;

use PDO;
use Yiisoft\Db\Driver\PDO\PDODriver as AbstractPDODriver;

final class PDODriver extends AbstractPDODriver
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
