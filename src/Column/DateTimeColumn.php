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

final class DateTimeColumn extends \Yiisoft\Db\Schema\Column\DateTimeColumn
{
    public function dbTypecast(mixed $value): string|ExpressionInterface|null
    {
        $value = parent::dbTypecast($value);

        if (!is_string($value)) {
            return $value;
        }

        $value = str_replace(["'", '"', "\000", "\032"], '', $value);

        return match ($this->getType()) {
            ColumnType::TIMESTAMP, ColumnType::DATETIME, ColumnType::DATETIMETZ => new Expression("TIMESTAMP '$value'"),
            ColumnType::TIME, ColumnType::TIMETZ => new Expression("INTERVAL '$value' DAY(0) TO SECOND"),
            ColumnType::DATE => new Expression("DATE '$value'"),
            default => $value,
        };
    }

    public function phpTypecast(mixed $value): DateTimeImmutable|null
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
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        return $this->format ??= match ($this->getType()) {
            ColumnType::TIME, ColumnType::TIMETZ => '0 H:i:s' . $this->getMillisecondsFormat(),
            default => parent::getFormat(),
        };
    }

    protected function shouldConvertTimezone(): bool
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        return $this->shouldConvertTimezone ??= !empty($this->dbTimezone) && match ($this->getType()) {
            ColumnType::DATETIMETZ,
            ColumnType::DATE => false,
            default => true,
        };
    }
}
