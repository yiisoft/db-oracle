<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Builder;

use Exception;
use Yiisoft\Db\QueryBuilder\Conditions\Builder\LikeConditionBuilder as AbstractLikeConditionBuilder;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\LikeConditionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Quoter;

use function substr;

final class LikeConditionBuilder extends AbstractLikeConditionBuilder
{
    protected ?string $escapeCharacter = '!';

    /**
     * `\` is initialized in {@see buildLikeCondition()} method since we need to choose replacement value based on
     * {@see Quoter::quoteValue()}.
     */
    protected array $escapingReplacements = [
        '%' => '!%',
        '_' => '!_',
        '!' => '!!',
    ];

    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
        parent::__construct($queryBuilder);
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
}
