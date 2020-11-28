<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use PDO;
use Yiisoft\Db\Connection\Connection as AbstractConnection;

/**
 * Database connection class prefilled for ORACLE Server.
 */
final class Connection extends AbstractConnection
{
    private Schema $schema;

    public function createCommand(?string $sql = null, array $params = []): Command
    {
        if ($sql !== null) {
            $sql = $this->quoteSql($sql);
        }

        $command = new Command($this->getProfiler(), $this->getLogger(), $this, $this->getQueryCache(), $sql);

        return $command->bindValues($params);
    }

    /**
     * Returns the schema information for the database opened by this connection.
     *
     * @return Schema the schema information for the database opened by this connection.
     */
    public function getSchema(): Schema
    {
        return $this->schema = new Schema($this, $this->getSchemaCache());
    }

    protected function createPdoInstance(): PDO
    {
        return new PDO($this->getDsn(), $this->getUsername(), $this->getPassword(), $this->getAttributes());
    }

    protected function initConnection(): void
    {
        $pdo = $this->getPDO();

        if ($pdo !== null) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($this->getEmulatePrepare() !== null && constant('PDO::ATTR_EMULATE_PREPARES')) {
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->getEmulatePrepare());
            }
        }
    }

    /**
     * Returns the name of the DB driver.
     *
     * @return string name of the DB driver
     */
    public function getDriverName(): string
    {
        return 'oci';
    }
}
