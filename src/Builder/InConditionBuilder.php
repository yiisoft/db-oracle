<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Builder;

use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\InCondition;

use function array_slice;
use function array_unshift;
use function count;
use function is_array;

/**
 * Build an object of {@see InCondition} into SQL expressions for Oracle Server.
 */
final class InConditionBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\InConditionBuilder
{
    /**
     * The Method builds the raw SQL from the $expression that won't be additionally escaped or quoted.
     *
     * @param InCondition $expression The expression to build.
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The raw SQL that won't be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $splitCondition = $this->splitCondition($expression, $params);

        return $splitCondition ?? parent::build($expression, $params);
    }

    /**
     * Oracle DBMS doesn't support more than 1000 parameters in `IN` condition.
     *
     * This method splits long `IN` condition into series of smaller ones.
     *
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string|null `null` when split isn't required. Otherwise - built SQL condition.
     */
    protected function splitCondition(InCondition $condition, array &$params): string|null
    {
        $operator = $condition->operator;
        $values = $condition->values;
        $column = $condition->column;

        if (!is_array($values)) {
            return null;
        }

        $maxParameters = 1000;
        $count = count($values);

        if ($count <= $maxParameters) {
            return null;
        }

        $slices = [];

        for ($i = 0; $i < $count; $i += $maxParameters) {
            $slices[] = $this->queryBuilder->createConditionFromArray(
                [$operator, $column, array_slice($values, $i, $maxParameters)]
            );
        }

        array_unshift($slices, ($operator === 'IN') ? 'OR' : 'AND');

        return $this->queryBuilder->buildCondition($slices, $params);
    }
}
