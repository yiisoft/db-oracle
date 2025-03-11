<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Column;

use Yiisoft\Db\Schema\Column\AbstractJsonColumn;

use function is_resource;
use function is_string;
use function json_decode;
use function stream_get_contents;

use const JSON_THROW_ON_ERROR;

/**
 * Represents a JSON column with eager parsing values retrieved from the database.
 */
final class JsonColumn extends AbstractJsonColumn
{
    /**
     * @throws \JsonException
     */
    public function phpTypecast(mixed $value): mixed
    {
        if (is_string($value)) {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        if (is_resource($value)) {
            /** @var string */
            $value = stream_get_contents($value);
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        return $value;
    }
}
