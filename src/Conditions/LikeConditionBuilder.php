<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Conditions;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Conditions\LikeConditionBuilder as AbstractLikeConditionBuilder;

final class LikeConditionBuilder extends AbstractLikeConditionBuilder
{
    /**
     * {@inheritdoc}
     */
    protected ?string $escapeCharacter = '!';
    /**
     * `\` is initialized in [[buildLikeCondition()]] method since
     * we need to choose replacement value based on [[\Yiisoft\Db\Schema::quoteValue()]].
     * {@inheritdoc}
     */
    protected array $escapingReplacements = [
        '%' => '!%',
        '_' => '!_',
        '!' => '!!',
    ];

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        if (!isset($this->escapingReplacements['\\'])) {
            /*
             * Different pdo_oci8 versions may or may not implement PDO::quote(), so
             * Yiisoft\Db\Schema::quoteValue() may or may not quote \.
             */
            $this->escapingReplacements['\\'] = substr($this->queryBuilder->getDb()->quoteValue('\\'), 1, -1);
        }

        return parent::build($expression, $params);
    }
}
