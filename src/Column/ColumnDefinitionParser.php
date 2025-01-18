<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Column;

use function preg_match;
use function strlen;
use function strtolower;
use function substr;

/**
 * Parses column definition string. For example, `string(255)` or `int unsigned`.
 */
final class ColumnDefinitionParser extends \Yiisoft\Db\Syntax\ColumnDefinitionParser
{
    private const TYPE_PATTERN = '/^('
        . 'timestamp\s*(?:\((\d+)\))? with(?: local)? time zone'
        . '|interval year\s*(?:\(\d+\))? to month'
        . ')|('
        . 'interval day\s*(?:\(\d+\))? to second'
        . '|\w*'
        . ')\s*(?:\(([^)]+)\))?\s*'
        . '/i';

    public function parse(string $definition): array
    {
        preg_match(self::TYPE_PATTERN, $definition, $matches);

        $type = strtolower($matches[3] ?? $matches[1]);
        $info = ['type' => $type];

        $typeDetails = $matches[4] ?? $matches[2] ?? '';

        if ($typeDetails !== '') {
            if ($type === 'enum') {
                $info += $this->enumInfo($typeDetails);
            } else {
                $info += $this->sizeInfo($typeDetails);
            }
        }

        $extra = substr($definition, strlen($matches[0]));

        return $info + $this->extraInfo($extra);
    }
}
