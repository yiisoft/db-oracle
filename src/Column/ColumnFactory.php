<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Column;

use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function preg_replace;
use function strtolower;

final class ColumnFactory extends AbstractColumnFactory
{
    /**
     * The mapping from physical column types (keys) to abstract column types (values).
     *
     * @link https://docs.oracle.com/en/database/oracle/oracle-database/23/sqlrf/Data-Types.html
     *
     * @var string[]
     *
     * @psalm-suppress MissingClassConstType
     */
    private const TYPE_MAP = [
        'char' => SchemaInterface::TYPE_CHAR,
        'nchar' => SchemaInterface::TYPE_CHAR,
        'varchar2' => SchemaInterface::TYPE_STRING,
        'nvarchar2' => SchemaInterface::TYPE_STRING,
        'clob' => SchemaInterface::TYPE_TEXT,
        'nclob' => SchemaInterface::TYPE_TEXT,
        'blob' => SchemaInterface::TYPE_BINARY,
        'bfile' => SchemaInterface::TYPE_BINARY,
        'long raw' => SchemaInterface::TYPE_BINARY,
        'raw' => SchemaInterface::TYPE_BINARY,
        'number' => SchemaInterface::TYPE_DECIMAL,
        'binary_float' => SchemaInterface::TYPE_FLOAT, // 32 bit
        'binary_double' => SchemaInterface::TYPE_DOUBLE, // 64 bit
        'float' => SchemaInterface::TYPE_DOUBLE, // 126 bit
        'date' => SchemaInterface::TYPE_DATE,
        'interval day to second' => SchemaInterface::TYPE_TIME,
        'timestamp' => SchemaInterface::TYPE_TIMESTAMP,
        'timestamp with time zone' => SchemaInterface::TYPE_TIMESTAMP,
        'timestamp with local time zone' => SchemaInterface::TYPE_TIMESTAMP,

        /** Deprecated */
        'long' => SchemaInterface::TYPE_TEXT,
    ];

    protected function getType(string $dbType, array $info = []): string
    {
        $dbType = strtolower($dbType);

        if ($dbType === 'number') {
            $scale = isset($info['scale']) ? (int) $info['scale'] : null;

            return match ($scale) {
                null => SchemaInterface::TYPE_DOUBLE,
                0 => SchemaInterface::TYPE_INTEGER,
                default => SchemaInterface::TYPE_DECIMAL,
            };
        }

        $dbType = preg_replace('/\([^)]+\)/', '', $dbType);

        if ($dbType === 'interval day to second' && isset($info['precision']) && $info['precision'] > 0) {
            return SchemaInterface::TYPE_STRING;
        }

        return self::TYPE_MAP[$dbType] ?? SchemaInterface::TYPE_STRING;
    }

    public function fromType(string $type, array $info = []): ColumnSchemaInterface
    {
        if ($type === SchemaInterface::TYPE_BINARY) {
            return (new BinaryColumnSchema($type))->load($info);
        }

        return parent::fromType($type, $info);
    }
}
