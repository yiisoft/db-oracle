<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Builder;

use Exception;
use Yiisoft\Db\QueryBuilder\Condition\Interface\LikeConditionInterface;
use Yiisoft\Db\Schema\Quoter;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function substr;

/**
 * LikeConditionBuilder builds conditions for {@see `\Yiisoft\Db\QueryBuilder\Condition\LikeCondition`} LIKE operator
 * for Oracle Server.
 */
final class LikeConditionBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeConditionBuilder
{
    private string $escapeCharacter = '!';

    /**
     * `\` is initialized in {@see buildLikeCondition()} method since we need to choose replacement value based on
     * {@see Quoter::quoteValue()}.
     */
    protected array $escapingReplacements = [
        '%' => '!%',
        '_' => '!_',
        '!' => '!!',
    ];

    public function __construct(QueryBuilderInterface $queryBuilder)
    {
        parent::__construct($queryBuilder, $this->getEscapeSql());
    }

    /**
     * @throws Exception
     */
    public function build(LikeConditionInterface $expression, array &$params = []): string
    {
        if (!isset($this->escapingReplacements['\\'])) {
            /*
             * Different pdo_oci8 versions may or may not implement PDO::quote(), so {@see Quoter::quoteValue()} may or
             * may not quote \.
             */
            $this->escapingReplacements['\\'] = substr((string) $this->queryBuilder->quoter()->quoteValue('\\'), 1, -1);
        }

        return parent::build($expression, $params);
    }

    /**
     * @return string character used to escape special characters in LIKE conditions.
     * By default, it's assumed to be `!`.
     */
    private function getEscapeSql(): string
    {
        return $this->escapeCharacter !== '' ? " ESCAPE '$this->escapeCharacter'" : '';
    }
}
