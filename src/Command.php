<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use PDO;
use Yiisoft\Db\Command\Command as AbstractCommand;

/**
 * Command represents an Oracle SQL statement to be executed against a database.
 */
final class Command extends AbstractCommand
{
    protected function bindPendingParams(): void
    {
        $paramsPassedByReference = [];
        $pdoStatement = $this->getPdoStatement();

        foreach ($this->pendingParams as $name => $value) {
            if (PDO::PARAM_STR === $value[1] && $pdoStatement !== null) {
                $paramsPassedByReference[$name] = $value[0];
                $pdoStatement->bindParam(
                    $name,
                    $paramsPassedByReference[$name],
                    $value[1],
                    strlen($value[0])
                );
            } elseif ($pdoStatement !== null) {
                $pdoStatement->bindValue($name, $value[0], $value[1]);
            }
        }

        $this->pendingParams = [];
    }
}
