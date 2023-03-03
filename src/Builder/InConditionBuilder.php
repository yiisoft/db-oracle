<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Builder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\InConditionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function array_slice;
use function array_unshift;
use function count;
use function is_array;

/**
 * InConditionBuilder builds conditions for {@see `\Yiisoft\Db\QueryBuilder\Condition\InCondition`} IN operator for
 * Oracle Server.
 */
final class InConditionBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\InConditionBuilder
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
        parent::__construct($queryBuilder);
    }

    /**
     * Method builds the raw SQL from the $expression that will not be additionally escaped or quoted.
     *
     * @param ExpressionInterface $expression The expression to be built.
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The raw SQL that will not be additionally escaped or quoted.
     *
     * @psalm-param InConditionInterface $expression
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $splitCondition = $this->splitCondition($expression, $params);

        return $splitCondition ?? parent::build($expression, $params);
    }

    /**
     * Oracle DBMS does not support more than 1000 parameters in `IN` condition.
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
     * @return string|null `null` when split is not required. Otherwise - built SQL condition.
     */
    protected function splitCondition(InConditionInterface $condition, array &$params): string|null
    {
        $operator = $condition->getOperator();
        $values = $condition->getValues();
        $column = $condition->getColumn();

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
