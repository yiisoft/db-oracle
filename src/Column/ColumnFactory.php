<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Column\AbstractColumnFactory;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function date_create_immutable;
use function preg_match;
use function rtrim;
use function strcasecmp;

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
        'timestamp' => ColumnType::DATETIME,
        'timestamp with time zone' => ColumnType::DATETIMETZ,
        'timestamp with local time zone' => ColumnType::DATETIME,
        'interval day to second' => ColumnType::STRING,
        'interval year to month' => ColumnType::STRING,
        'json' => ColumnType::JSON,

        /** Deprecated */
        'long' => ColumnType::TEXT,
    ];
    private const DATETIME_REGEX = "/^(?:TIMESTAMP|DATE|INTERVAL|to_timestamp(?:_tz)?\(|to_date\(|to_dsinterval\()\s*'(?:\d )?([^']+)/";

    public function fromPseudoType(string $pseudoType, array $info = []): ColumnInterface
    {
        return parent::fromPseudoType($pseudoType, $info)->unsigned(false);
    }

    protected function columnDefinitionParser(): ColumnDefinitionParser
    {
        return new ColumnDefinitionParser();
    }

    protected function getType(string $dbType, array $info = []): string
    {
        if ($dbType === 'number') {
            return match ($info['scale'] ?? null) {
                null => ColumnType::DOUBLE,
                0 => ColumnType::INTEGER,
                default => ColumnType::DECIMAL,
            };
        }

        if (isset($info['check'], $info['name'])) {
            if (strcasecmp($info['check'], '"' . $info['name'] . '" is json') === 0) {
                return ColumnType::JSON;
            }

            if (isset($info['size'])
                && $dbType === 'char'
                && $info['size'] === 1
                && strcasecmp($info['check'], '"' . $info['name'] . '" in (0,1)') === 0
            ) {
                return ColumnType::BOOLEAN;
            }
        }

        if ($dbType === 'interval day to second' && isset($info['scale']) && $info['scale'] === 0) {
            return ColumnType::TIME;
        }

        return parent::getType($dbType, $info);
    }

    protected function getColumnClass(string $type, array $info = []): string
    {
        return match ($type) {
            ColumnType::BINARY => BinaryColumn::class,
            ColumnType::BOOLEAN => BooleanColumn::class,
            ColumnType::DATETIME => DateTimeColumn::class,
            ColumnType::DATETIMETZ => DateTimeColumn::class,
            ColumnType::TIME => DateTimeColumn::class,
            ColumnType::TIMETZ => DateTimeColumn::class,
            ColumnType::DATE => DateTimeColumn::class,
            ColumnType::JSON => JsonColumn::class,
            default => parent::getColumnClass($type, $info),
        };
    }

    protected function normalizeNotNullDefaultValue(string $defaultValue, ColumnInterface $column): mixed
    {
        $value = parent::normalizeNotNullDefaultValue(rtrim($defaultValue), $column);

        if ($column instanceof DateTimeColumn
            && $value instanceof Expression
            && preg_match(self::DATETIME_REGEX, (string) $value, $matches) === 1
        ) {
            return date_create_immutable($matches[1]) !== false
                ? $column->phpTypecast($matches[1])
                : new Expression($matches[1]);
        }

        return $value;
    }
}
