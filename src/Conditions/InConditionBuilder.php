<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Conditions;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Conditions\InCondition;
use Yiisoft\Db\Query\Conditions\InConditionBuilder as AbstractInConditionBuilder;

final class InConditionBuilder extends AbstractInConditionBuilder
{
    /**
     * Method builds the raw SQL from the $expression that will not be additionally
     * escaped or quoted.
     *
     * @param ExpressionInterface|InCondition $expression the expression to be built.
     * @param array $params the binding parameters.
     *
     * @return string the raw SQL that will not be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $splitCondition = $this->splitCondition($expression, $params);
        if ($splitCondition !== null) {
            return $splitCondition;
        }

        return parent::build($expression, $params);
    }

    /**
     * Oracle DBMS does not support more than 1000 parameters in `IN` condition.
     * This method splits long `IN` condition into series of smaller ones.
     *
     * @param InCondition $condition
     * @param array $params the binding parameters.
     *
     * @return mixed null when split is not required. Otherwise - built SQL condition.
     */
    protected function splitCondition(InCondition $condition, &$params)
    {
        $operator = $condition->getOperator();
        $values = $condition->getValues();
        $column = $condition->getColumn();

        if ($values instanceof \Traversable) {
            $values = iterator_to_array($values);
        }

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
            $slices[] = $this->queryBuilder->createConditionFromArray([$operator, $column, array_slice($values, $i, $maxParameters)]);
        }
        array_unshift($slices, ($operator === 'IN') ? 'OR' : 'AND');

        return $this->queryBuilder->buildCondition($slices, $params);
    }
}
