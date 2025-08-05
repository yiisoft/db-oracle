<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Builder;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Like;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function substr;

/**
 * Build an object of {@see Like} into SQL expressions for Oracle Server.
 */
final class LikeBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeBuilder
{
    private string $escapeCharacter = '!';

    /**
     * `\` is initialized in {@see buildLike()} method since there is a need to choose replacement value
     * based on {@see Quoter::quoteValue()}.
     */
    protected array $escapingReplacements = [
        '%' => '!%',
        '_' => '!_',
        '!' => '!!',
    ];

    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
        parent::__construct($queryBuilder, $this->getEscapeSql());

        /**
         * Different pdo_oci8 versions may or may not implement `PDO::quote()`, so {@see Quoter::quoteValue()} may or
         * may not quote `\`.
         */
        $this->escapingReplacements['\\'] = substr($this->queryBuilder->getQuoter()->quoteValue('\\'), 1, -1);
    }

    protected function prepareColumn(Like $condition, array &$params): string
    {
        $column = parent::prepareColumn($condition, $params);

        if ($condition->caseSensitive === false) {
            $column = 'LOWER(' . $column . ')';
        }

        return $column;
    }

    protected function preparePlaceholderName(
        string|ExpressionInterface $value,
        Like $condition,
        array &$params,
    ): string {
        $placeholderName = parent::preparePlaceholderName($value, $condition, $params);

        if ($condition->caseSensitive === false) {
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
