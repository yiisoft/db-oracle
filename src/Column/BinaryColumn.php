<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Column;

use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Column\BinaryColumn as BaseBinaryColumn;

use function is_string;

final class BinaryColumn extends BaseBinaryColumn
{
    public function dbTypecast(mixed $value): mixed
    {
        if ($this->getDbType() === 'BLOB') {
            if ($value instanceof ParamInterface && is_string($value->getValue())) {
                /** @var string */
                $value = $value->getValue();
            }

            if (is_string($value)) {
                return new Expression('TO_BLOB(UTL_RAW.CAST_TO_RAW(:value))', ['value' => $value]);
            }
        }

        return parent::dbTypecast($value);
    }
}
