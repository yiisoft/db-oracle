<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use PDO;
use Yiisoft\Db\Connection\Connection as AbstractConnection;
use Yiisoft\Db\Oracle\Command;
use Yiisoft\Db\Oracle\Schema;

use function in_array;

/**
 * Database connection class prefilled for ORACLE Server.
 */
final class Connection extends AbstractConnection
{
    private ?Schema $schema = null;

    public function createCommand(?string $sql = null, array $params = []): Command
    {
        if ($sql !== null) {
            $sql = $this->quoteSql($sql);
        }

        $command = new Command($this->getProfiler(), $this->getLogger(), $this, $sql);

        return $command->bindValues($params);
    }

    /**
     * Returns the schema information for the database opened by this connection.
     *
     * @return Schema the schema information for the database opened by this connection.
     */
    public function getSchema(): Schema
    {
        if ($this->schema !== null) {
            return $this->schema;
        }

        return $this->schema = new Schema($this);
    }

    protected function createPdoInstance(): \PDO
    {
        return new PDO($this->getDsn(), $this->getUsername(), $this->getPassword(), $this->getAttributes());
    }

    protected function initConnection(): void
    {
        if ($this->getPDO() !== null) {
            $this->getPDO()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($this->getEmulatePrepare() !== null && constant('PDO::ATTR_EMULATE_PREPARES')) {
                $this->getPDO()->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->getEmulatePrepare());
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
