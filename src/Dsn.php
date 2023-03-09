<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Connection\AbstractDsn;

/**
 * Implement a Data Source Name (DSN) for an MySQL, MariaDb Server.
 *
 * @link https://www.php.net/manual/en/ref.pdo-oci.connection.php
 */
final class Dsn extends AbstractDsn
{
    /**
     * @psalm-param string[] $options
     */
    public function __construct(
        private string $driver,
        private string $host,
        private string $databaseName,
        private string $port = '1521',
        private array $options = []
    ) {
        parent::__construct($driver, $host, $databaseName, $port, $options);
    }

    /**
     * @return string The Data Source Name, or DSN, contains the information required to connect to the database.
     *
     * Please refer to the [PHP manual](http://php.net/manual/en/pdo.construct.php) on the format of the DSN string.
     *
     * The `driver` array key is used as the driver prefix of the DSN, all further key-value pairs are rendered as
     * `key=value` and concatenated by `;`. For example:
     *
     * ```php
     * $dsn = new Dsn('oci', '127.0.0.1', 'yiitest', '3306');
     * $connection = new Connection($this->cache, $this->logger, $this->profiler, $dsn->getDsn());
     * ```
     *
     * Will result in the DSN string `mysql:host=127.0.0.1;dbname=yiitest;port=3306`.
     */
    public function asString(): string
    {
        $dsn = match ($this->port) {
            '' => "$this->driver:" . "dbname=$this->host/$this->databaseName",
            default => "$this->driver:" . "dbname=$this->host:$this->port/$this->databaseName",
        };

        $parts = [];

        foreach ($this->options as $key => $value) {
            $parts[] = "$key=$value";
        }

        if (!empty($parts)) {
            $dsn .= ';' . implode(';', $parts);
        }

        return $dsn;
    }
}
