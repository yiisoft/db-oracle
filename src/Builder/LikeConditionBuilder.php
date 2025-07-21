<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Builder;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\LikeCondition;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Quoter;

use function substr;

/**
 * Build an object of {@see LikeCondition} into SQL expressions for Oracle Server.
 */
final class LikeConditionBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeConditionBuilder
{
    private string $escapeCharacter = '!';

    /**
     * `\` is initialized in {@see buildLikeCondition()} method since there is a need to choose replacement value
     * based on {@see Quoter::quoteValue()}.
     */
    protected array $escapingReplacements = [
        '%' => '!%',
        '_' => '!_',
        '!' => '!!',
    ];

    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
        parent::__construct($queryBuilder, $this->getEscapeSql());
    }

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        if (!isset($this->escapingReplacements['\\'])) {
            /*
             * Different pdo_oci8 versions may or may not implement `PDO::quote()`, so {@see Quoter::quoteValue()} may or
             * may not quote `\`.
             */
            $this->escapingReplacements['\\'] = substr($this->queryBuilder->getQuoter()->quoteValue('\\'), 1, -1);
        }

        return parent::build($expression, $params);
    }

    protected function prepareColumn(LikeCondition $expression, array &$params): string
    {
        $column = parent::prepareColumn($expression, $params);

        if ($expression->caseSensitive === false) {
            $column = 'LOWER(' . $column . ')';
        }

        return $column;
    }

    protected function preparePlaceholderName(
        string|ExpressionInterface $value,
        LikeCondition $expression,
        ?array $escape,
        array &$params,
    ): string {
        $placeholderName = parent::preparePlaceholderName($value, $expression, $escape, $params);

        if ($expression->caseSensitive === false) {
            $placeholderName = 'LOWER(' . $placeholderName . ')';
        }

        return $placeholderName;
    }

    /**
     * @return string Character used to escape special characters in `LIKE` conditions. By default, it's assumed to be
     * `!`.
     */
    private function getEscapeSql(): string
    {
        return $this->escapeCharacter !== '' ? " ESCAPE '$this->escapeCharacter'" : '';
    }
}
