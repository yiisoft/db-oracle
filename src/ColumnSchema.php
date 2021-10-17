<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Pdo\PdoValue;
use Yiisoft\Db\Schema\ColumnSchema as AbstractColumnSchema;
use Yiisoft\Db\Schema\Schema;

/**
 * Class ColumnSchema for Oracle database
 */
final class ColumnSchema extends AbstractColumnSchema
{
    public function dbTypecast($value)
    {
        if ($this->getType() === Schema::TYPE_BINARY && $this->getDbType() === 'BLOB') {
            if ($value instanceof PdoValue && is_string($value->getValue())) {
                $value = $value->getValue();
            }
            if (is_string($value)) {
                $placeholder = uniqid('exp_' . preg_replace('/[^a-z0-9]/i', '', $this->getName()));
                return new Expression('TO_BLOB(UTL_RAW.CAST_TO_RAW(:' . $placeholder . '))', [$placeholder => $value]);
            }
        }

        return parent::dbTypecast($value);
    }
}
