<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PhpType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Column\AbstractColumn;

final class BooleanColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::BOOLEAN;

    public function dbTypecast(mixed $value): string|ExpressionInterface|null
    {
        return match ($value) {
            true => '1',
            false => '0',
            null, '' => null,
            default => $value instanceof ExpressionInterface ? $value : ($value ? '1' : '0'),
        };
    }

    public function getPhpType(): string
    {
        return PhpType::BOOL;
    }

    public function phpTypecast(mixed $value): bool|null
    {
        if ($value === null) {
            return null;
        }

        return (bool) $value;
    }
}
