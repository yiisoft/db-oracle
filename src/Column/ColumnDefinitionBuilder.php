<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\QueryBuilder\AbstractColumnDefinitionBuilder;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

use function ceil;
use function log10;
use function strtoupper;

final class ColumnDefinitionBuilder extends AbstractColumnDefinitionBuilder
{
    protected const AUTO_INCREMENT_KEYWORD = 'GENERATED BY DEFAULT AS IDENTITY';

    protected const GENERATE_UUID_EXPRESSION = 'sys_guid()';

    protected const TYPES_WITH_SIZE = [
        'char',
        'nchar',
        'character',
        'varchar',
        'varchar2',
        'nvarchar2',
        'number',
        'float',
        'timestamp',
        'interval day(0) to second',
        'raw',
        'urowid',
    ];

    protected const TYPES_WITH_SCALE = [
        'number',
    ];

    public function build(ColumnSchemaInterface $column): string
    {
        return $this->buildType($column)
            . $this->buildAutoIncrement($column)
            . $this->buildDefault($column)
            . $this->buildPrimaryKey($column)
            . $this->buildUnique($column)
            . $this->buildNotNull($column)
            . $this->buildCheck($column)
            . $this->buildReferences($column)
            . $this->buildExtra($column);
    }

    protected function buildOnDelete(string $onDelete): string
    {
        return match ($onDelete = strtoupper($onDelete)) {
            'CASCADE',
            'SET NULL' => " ON DELETE $onDelete",
            default => '',
        };
    }

    protected function buildOnUpdate(string $onUpdate): string
    {
        return '';
    }

    protected function getDbType(ColumnSchemaInterface $column): string
    {
        $size = $column->getSize();

        /** @psalm-suppress DocblockTypeContradiction */
        return $column->getDbType() ?? match ($column->getType()) {
            ColumnType::BOOLEAN => 'number(1)',
            ColumnType::BIT => match (true) {
                $size === null => 'number(38)',
                $size <= 126 => 'number(' . ceil(log10(2 ** $size)) . ')',
                default => 'raw(' . ceil($size / 8) . ')',
            },
            ColumnType::TINYINT => 'number(' . ($size ?? 3) . ')',
            ColumnType::SMALLINT => 'number(' . ($size ?? 5) . ')',
            ColumnType::INTEGER => 'number(' . ($size ?? 10) . ')',
            ColumnType::BIGINT => 'number(' . ($size ?? 20) . ')',
            ColumnType::FLOAT => 'binary_float',
            ColumnType::DOUBLE => 'binary_double',
            ColumnType::DECIMAL => 'number(' . ($size ?? 10) . ',' . ($column->getScale() ?? 0) . ')',
            ColumnType::MONEY => 'number(' . ($size ?? 19) . ',' . ($column->getScale() ?? 4) . ')',
            ColumnType::CHAR => 'char',
            ColumnType::STRING => 'varchar2(' . ($size ?? 255) . ')',
            ColumnType::TEXT => 'clob',
            ColumnType::BINARY => 'blob',
            ColumnType::UUID => 'raw(16)',
            ColumnType::DATETIME => 'timestamp',
            ColumnType::TIMESTAMP => 'timestamp',
            ColumnType::DATE => 'date',
            ColumnType::TIME => 'interval day(0) to second',
            ColumnType::ARRAY => 'clob',
            ColumnType::STRUCTURED => 'clob',
            ColumnType::JSON => 'clob',
            default => 'varchar2',
        };
    }
}
