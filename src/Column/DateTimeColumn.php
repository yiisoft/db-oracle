<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Column;

use DateTimeImmutable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;

use function explode;
use function is_string;
use function str_replace;

/**
 * Represents the metadata for a datetime column.
 *
 * > [!WARNING]
 * > Oracle DBMS converts `TIMESTAMP WITH LOCAL TIME ZONE` column type values from database session time zone
 * > to the database time zone for storage, and back from the database time zone to the session time zone when retrieve
 * > the values.
 *
 * `TIMESTAMP WITH LOCAL TIME ZONE` database type does not store time zone offset and require to convert datetime values
 * to the database session time zone before insert and back to the PHP time zone after retrieve the values.
 * This will be done in the {@see dbTypecast()} and {@see phpTypecast()} methods and guarantees that the values
 * are stored in the database in the correct time zone.
 *
 * To avoid possible time zone issues with the datetime values conversion, it is recommended to set the PHP and database
 * time zones to UTC.
 */
final class DateTimeColumn extends \Yiisoft\Db\Schema\Column\DateTimeColumn
{
    public function dbTypecast(mixed $value): float|int|string|ExpressionInterface|null
    {
        $value = parent::dbTypecast($value);

        if (!is_string($value)) {
            return $value;
        }

        $value = str_replace(["'", '"', "\000", "\032"], '', $value);

        return match ($this->getType()) {
            ColumnType::TIMESTAMP, ColumnType::DATETIME, ColumnType::DATETIMETZ => new Expression("TIMESTAMP '$value'"),
            ColumnType::TIME, ColumnType::TIMETZ => new Expression(
                "INTERVAL '$value' DAY(0) TO SECOND" . (($size = $this->getSize()) !== null ? "($size)" : ''),
            ),
            ColumnType::DATE => new Expression("DATE '$value'"),
            default => $value,
        };
    }

    public function phpTypecast(mixed $value): ?DateTimeImmutable
    {
        if (is_string($value) && match ($this->getType()) {
            ColumnType::TIME, ColumnType::TIMETZ => true,
            default => false,
        }) {
            $value = explode(' ', $value, 2)[1] ?? $value;
        }

        return parent::phpTypecast($value);
    }

    protected function getFormat(): string
    {
        return $this->format ??= match ($this->getType()) {
            ColumnType::TIME, ColumnType::TIMETZ => '0 H:i:s' . $this->getMillisecondsFormat(),
            default => parent::getFormat(),
        };
    }

    protected function shouldConvertTimezone(): bool
    {
        return $this->shouldConvertTimezone ??= !empty($this->dbTimezone) && match ($this->getType()) {
            ColumnType::DATETIMETZ,
            ColumnType::DATE => false,
            default => true,
        };
    }
}
