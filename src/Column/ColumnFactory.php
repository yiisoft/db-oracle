<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function in_array;
use function preg_replace;
use function rtrim;
use function strtolower;

final class ColumnFactory extends AbstractColumnFactory
{
    /**
     * The mapping from physical column types (keys) to abstract column types (values).
     *
     * @link https://docs.oracle.com/en/database/oracle/oracle-database/23/sqlrf/Data-Types.html
     *
     * @var string[]
     * @psalm-var array<string, ColumnType::*>
     */
    protected const TYPE_MAP = [
        'char' => ColumnType::CHAR,
        'nchar' => ColumnType::CHAR,
        'character' => ColumnType::CHAR,
        'varchar' => ColumnType::STRING,
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

    protected function columnDefinitionParser(): ColumnDefinitionParser
    {
        return new ColumnDefinitionParser();
    }

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

        if (in_array($dbType, [
            'timestamp',
            'timestamp with time zone',
            'timestamp with local time zone',
            'interval day to second',
            'interval year to month',
        ], true)) {
            [$info['size'], $info['scale']] = [$info['scale'] ?? null, $info['size'] ?? null];

            if ($dbType === 'interval day to second' && $info['scale'] > 0) {
                return ColumnType::STRING;
            }
        }

        return parent::getType($dbType, $info);
    }

    protected function getColumnClass(string $type, array $info = []): string
    {
        if ($type === ColumnType::BINARY) {
            return BinaryColumn::class;
        }

        return parent::getColumnClass($type, $info);
    }

    protected function normalizeNotNullDefaultValue(string $defaultValue, ColumnInterface $column): mixed
    {
        return parent::normalizeNotNullDefaultValue(rtrim($defaultValue), $column);
    }
}
