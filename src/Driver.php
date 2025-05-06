<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use PDO;
use Yiisoft\Db\Driver\Pdo\AbstractPdoDriver;

/**
 * Implements the Oracle Server driver based on the PDO (PHP Data Objects) extension.
 *
 * @see https://www.php.net/manual/en/ref.pdo-oci.php
 */
final class Driver extends AbstractPdoDriver
{
    public function createConnection(): PDO
    {
        $this->attributes += [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

        $pdo = parent::createConnection();

        $pdo->exec(
            <<<SQL
            ALTER SESSION SET
                NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SSXFF'
                NLS_TIMESTAMP_TZ_FORMAT = 'YYYY-MM-DD HH24:MI:SSXFFTZH:TZM'
                NLS_TIME_FORMAT = 'HH24:MI:SSXFF'
                NLS_TIME_TZ_FORMAT = 'HH24:MI:SSXFFTZH:TZM'
                NLS_DATE_FORMAT = 'YYYY-MM-DD'
            SQL
        );

        return $pdo;
    }

    public function getDriverName(): string
    {
        return 'oci';
    }
}
