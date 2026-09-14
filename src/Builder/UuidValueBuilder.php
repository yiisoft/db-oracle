<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Builder;

use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Value\UuidValue;

use function str_replace;

/**
 * Builds a {@see UuidValue} expression for Oracle.
 *
 * Oracle stores a UUID as 16 raw bytes in a `raw(16)` column, and PDO_OCI cannot bind those bytes as a parameter:
 * bound as a large object it inserts `NULL`. So the value is emitted as a `HEXTORAW()` literal, the representation
 * this driver already uses for binary values.
 *
 * Inlining is safe here because `UuidValue` normalizes the value to the canonical form, which leaves exactly 32
 * hexadecimal characters once the dashes are removed.
 *
 * @implements ExpressionBuilderInterface<UuidValue>
 */
final class UuidValueBuilder implements ExpressionBuilderInterface
{
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        /** @var UuidValue $expression */
        return "HEXTORAW('" . str_replace('-', '', $expression->value) . "')";
    }
}
