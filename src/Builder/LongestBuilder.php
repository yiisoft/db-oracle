<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Builder;

use Yiisoft\Db\Expression\Function\Builder\MultiOperandFunctionBuilder;
use Yiisoft\Db\Expression\Function\Greatest;
use Yiisoft\Db\Expression\Function\Longest;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;

/**
 * Builds SQL representation of function expressions which returns the longest string from a set of operands.
 *
 * ```SQL
 * (SELECT value FROM (
 *     SELECT operand1 AS value FROM DUAL
 *     UNION
 *     SELECT operand2 AS value FROM DUAL
 * ) ORDER BY LENGTH(value) DESC FETCH FIRST 1 ROWS ONLY)
 * ```
 *
 * @extends MultiOperandFunctionBuilder<Longest>
 */
final class LongestBuilder extends MultiOperandFunctionBuilder
{
    /**
     * Builds a SQL expression to represent the function which returns the longest string.
     *
     * @param Greatest $expression The expression to build.
     * @param array $params The parameters to bind.
     *
     * @return string The SQL expression.
     */
    protected function buildFromExpression(MultiOperandFunction $expression, array &$params): string
    {
        $selects = [];

        foreach ($expression->getOperands() as $operand) {
            $selects[] = 'SELECT ' . $this->buildOperand($operand, $params) . ' AS value FROM DUAL';
        }

        $unions = implode(' UNION ', $selects);

        return "(SELECT value FROM ($unions) ORDER BY LENGTH(value) DESC FETCH FIRST 1 ROWS ONLY)";
    }
}
