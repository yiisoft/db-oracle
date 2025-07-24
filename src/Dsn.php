<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Stringable;

/**
 * Represents a Data Source Name (DSN) for an Oracle Server that's used to configure a {@see Driver} instance.
 *
 * To get DSN in string format, use the `(string)` type casting operator.
 *
 * @link https://www.php.net/manual/en/ref.pdo-oci.connection.php
 */
final class Dsn implements Stringable
{
    /**
     * @psalm-param array<string,string> $options
     */
    public function __construct(
        public readonly string $driver = 'oci',
        public readonly string $host = '127.0.0.1',
        public readonly string $databaseName = '',
        public readonly string $port = '1521',
        public readonly array $options = [],
    ) {
    }

    /**
     * @return string The Data Source Name, or DSN, contains the information required to connect to the database.
     *
     * Please refer to the [PHP manual](https://php.net/manual/en/pdo.construct.php) on the format of the DSN string.
     *
     * The `driver` property is used as the driver prefix of the DSN. For example:
     *
     * ```php
     * $dsn = new Dsn('oci', 'localhost', 'yiitest', '1521', ['charset' => 'AL32UTF8']);
     * $driver = new Driver($dsn, 'username', 'password');
     * $connection = new Connection($driver, 'system', 'root');
     * ```
     *
     * Will result in the DSN string `oci:dbname=localhost:1521/yiitest;charset=AL32UTF8`.
     */
    public function __toString(): string
    {
        $dsn = "$this->driver:dbname=$this->host:$this->port";

        if ($this->databaseName !== '') {
            $dsn .= "/$this->databaseName";
        }

        foreach ($this->options as $key => $value) {
            $dsn .= ";$key=$value";
        }

        return $dsn;
    }
}
