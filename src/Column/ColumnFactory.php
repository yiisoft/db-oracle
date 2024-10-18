<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

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
        'char' => ColumnType::CHAR,
        'nchar' => ColumnType::CHAR,
        'varchar2' => ColumnType::STRING,
        'nvarchar2' => ColumnType::STRING,
        'clob' => ColumnType::TEXT,
        'nclob' => ColumnType::TEXT,
        'blob' => ColumnType::BINARY,
        'bfile' => ColumnType::BINARY,
        'long raw' => ColumnType::BINARY,
        'raw' => ColumnType::BINARY,
        'number' => ColumnType::DECIMAL,
        'binary_float' => ColumnType::FLOAT, // 32 bit
        'binary_double' => ColumnType::DOUBLE, // 64 bit
        'float' => ColumnType::DOUBLE, // 126 bit
        'date' => ColumnType::DATE,
        'interval day to second' => ColumnType::TIME,
        'timestamp' => ColumnType::TIMESTAMP,
        'timestamp with time zone' => ColumnType::TIMESTAMP,
        'timestamp with local time zone' => ColumnType::TIMESTAMP,

        /** Deprecated */
        'long' => ColumnType::TEXT,
    ];

    protected function getType(string $dbType, array $info = []): string
    {
        $dbType = strtolower($dbType);

        if ($dbType === 'number') {
            return match ($info['scale'] ?? null) {
                null => ColumnType::DOUBLE,
                0 => ColumnType::INTEGER,
                default => ColumnType::DECIMAL,
            };
        }

        $dbType = preg_replace('/\([^)]+\)/', '', $dbType);

        if ($dbType === 'interval day to second' && isset($info['size']) && $info['size'] > 0) {
            return ColumnType::STRING;
        }

        return self::TYPE_MAP[$dbType] ?? ColumnType::STRING;
    }

    public function fromType(string $type, array $info = []): ColumnSchemaInterface
    {
        if ($type === ColumnType::BINARY) {
            unset($info['type']);
            return new BinaryColumnSchema($type, ...$info);
        }

        return parent::fromType($type, $info);
    }

    protected function isDbType(string $dbType): bool
    {
        return isset(self::TYPE_MAP[$dbType]);
    }
}
