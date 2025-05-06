<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Driver\Pdo\PdoServerInfo;

final class ServerInfo extends PdoServerInfo
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private string $timezone;

    public function getTimezone(bool $refresh = false): string
    {
        /** @psalm-suppress TypeDoesNotContainType */
        if (!isset($this->timezone) || $refresh) {
            /** @var string */
            $this->timezone = $this->db->createCommand('SELECT SESSIONTIMEZONE FROM DUAL')->queryScalar();
        }

        return $this->timezone;
    }
}
