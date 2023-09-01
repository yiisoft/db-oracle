<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Helper\DbStringHelper;
use Yiisoft\Db\Schema\Column\AbstractColumnSchema;
use Yiisoft\Db\Schema\SchemaInterface;

use function is_float;
use function is_resource;
use function is_string;

final class BinaryColumnSchema extends AbstractColumnSchema
{
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->type(SchemaInterface::TYPE_BINARY);
        $this->phpType(SchemaInterface::PHP_TYPE_RESOURCE);
    }

    public function dbTypecast(mixed $value): mixed
    {
        if ($this->getDbType() === 'BLOB') {
            if ($value instanceof ParamInterface && is_string($value->getValue())) {
                /** @psalm-var string */
                $value = $value->getValue();
            }

            if (is_string($value)) {
                $placeholder = uniqid('exp_' . preg_replace('/[^a-z0-9]/i', '', $this->getName()));
                return new Expression('TO_BLOB(UTL_RAW.CAST_TO_RAW(:' . $placeholder . '))', [$placeholder => $value]);
            }
        }

        return match (true) {
            is_string($value) => new Param($value, PDO::PARAM_LOB),
            $value === null, is_resource($value), $value instanceof ExpressionInterface => $value,
            /** ensure type cast always has . as decimal separator in all locales */
            is_float($value) => DbStringHelper::normalizeFloat($value),
            $value === false => '0',
            default => (string) $value,
        };
    }
}
