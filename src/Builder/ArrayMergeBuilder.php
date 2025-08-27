<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Builder;

use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Expression\Function\Builder\MultiOperandFunctionBuilder;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;
use Yiisoft\Db\Schema\Column\AbstractArrayColumn;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function implode;
use function is_string;
use function rtrim;

/**
 * Builds SQL expressions which merge arrays for {@see ArrayMerge} objects.
 *
 * ```sql
 * (SELECT JSON_ARRAYAGG(value) AS value FROM (
 *     SELECT value FROM JSON_TABLE(operand1, '$[*]' COLUMNS(value int PATH '$'))
 *     UNION
 *     SELECT value FROM JSON_TABLE(operand2, '$[*]' COLUMNS(value int PATH '$'))
 * ))
 * ```
 *
 * @extends MultiOperandFunctionBuilder<ArrayMerge>
 */
final class ArrayMergeBuilder extends MultiOperandFunctionBuilder
{
    private const DEFAULT_OPERAND_TYPE = '';

    /**
     * Builds a SQL expression which merges arrays from the given {@see ArrayMerge} object.
     *
     * @param ArrayMerge $expression The expression to build.
     * @param array $params The parameters to bind.
     *
     * @return string The SQL expression.
     */
    protected function buildFromExpression(MultiOperandFunction $expression, array &$params): string
    {
        $selects = [];
        $operandType = $this->buildOperandType($expression->getType());

        foreach ($expression->getOperands() as $operand) {
            $builtOperand = $this->buildOperand($operand, $params);
            $selects[] = "SELECT value FROM JSON_TABLE($builtOperand, '$[*]' COLUMNS(value $operandType PATH '$'))";
        }

        $orderBy = $expression->getOrdered() ? ' ORDER BY value' : '';
        $unions = implode(' UNION ', $selects);

        return "(SELECT JSON_ARRAYAGG(value$orderBy) AS value FROM ($unions))";
    }

    private function buildOperandType(string|ColumnInterface $type): string
    {
        if (is_string($type)) {
            return $type === '' ? self::DEFAULT_OPERAND_TYPE : rtrim($type, '[]');
        }

        if ($type instanceof AbstractArrayColumn) {
            if ($type->getDimension() > 1) {
                return self::DEFAULT_OPERAND_TYPE;
            }

            $type = $type->getColumn();

            if ($type === null) {
                return self::DEFAULT_OPERAND_TYPE;
            }
        }

        return $this->queryBuilder->getColumnDefinitionBuilder()->buildType($type);
    }
}
