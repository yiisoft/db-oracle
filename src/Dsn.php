<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Connection\AbstractDsn;

/**
 * Implement a Data Source Name (DSN) for an Oracle Server.
 *
 * @link https://www.php.net/manual/en/ref.pdo-oci.connection.php
 */
final class Dsn extends AbstractDsn
{
    /**
     * @psalm-param array<string,string> $options
     */
    public function __construct(
        string $driver = 'oci',
        string $host = 'localhost',
        string|null $databaseName = null,
        string $port = '1521',
        array $options = []
    ) {
        parent::__construct($driver, $host, $databaseName, $port, $options);
    }

    /**
     * @return string The Data Source Name, or DSN, contains the information required to connect to the database.
     *
     * Please refer to the [PHP manual](https://php.net/manual/en/pdo.construct.php) on the format of the DSN string.
     *
     * The `driver` array key is used as the driver prefix of the DSN, all further key-value pairs are rendered as
     * `key=value` and concatenated by `;`. For example:
     *
     * ```php
     * $dsn = new Dsn('oci', 'localhost', 'yiitest', '1521', ['charset' => 'AL32UTF8']);
     * $connection = new Connection($dsn->asString(), 'system', 'root');
     * ```
     *
     * Will result in the DSN string `oci:dbname=localhost:1521/yiitest;charset=AL32UTF8`.
     */
    public function asString(): string
    {
        $driver = $this->getDriver();
        $host = $this->getHost();
        $databaseName = $this->getDatabaseName();
        $port = $this->getPort();
        $options = $this->getOptions();

        $dsn = "$driver:dbname=$host:$port";

        if (!empty($databaseName)) {
            $dsn .= "/$databaseName";
        }

        foreach ($options as $key => $value) {
            $dsn .= ";$key=$value";
        }

        return $dsn;
    }
}
