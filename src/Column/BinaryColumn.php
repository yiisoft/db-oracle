<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Column;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Schema\Column\BinaryColumn as BaseBinaryColumn;
use Yiisoft\Db\Schema\Data\StringableStream;

use function is_string;

final class BinaryColumn extends BaseBinaryColumn
{
    public function dbTypecast(mixed $value): mixed
    {
        if ($this->getDbType() === 'blob') {
            if ($value instanceof Param) {
                $value = $value->value;
            } elseif ($value instanceof StringableStream) {
                $value = $value->getValue();
            }

            if (is_string($value)) {
                return new Expression('TO_BLOB(UTL_RAW.CAST_TO_RAW(:value))', ['value' => $value]);
            }
        }

        return parent::dbTypecast($value);
    }
}
