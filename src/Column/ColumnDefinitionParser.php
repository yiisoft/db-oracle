<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Column;

use function preg_match;
use function preg_replace;
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
        . '|interval year\s*(?:\((\d+)\))? to month'
        . ')|('
        . 'interval day\s*(?:\((\d+)\))? to second'
        . '|long raw'
        . '|\w*'
        . ')\s*(?:\(([^)]+)\))?(\[[\d\[\]]*\])?\s*'
        . '/i';

    public function parse(string $definition): array
    {
        preg_match(self::TYPE_PATTERN, $definition, $matches);

        /** @var string $type */
        $type = preg_replace('/\s*\(\d+\)/', '', $matches[4] ?? $matches[1]);
        $type = strtolower($type);
        $info = ['type' => $type];

        $typeDetails = $matches[6] ?? $matches[2] ?? '';

        if ($typeDetails !== '') {
            if ($type === 'enum') {
                $info += $this->enumInfo($typeDetails);
            } else {
                $info += $this->sizeInfo($typeDetails);
            }
        }

        $scale = $matches[5] ?? $matches[3] ?? '';

        if ($scale !== '') {
            $info += ['scale' => (int) $scale];
        }

        if (isset($matches[7])) {
            /** @psalm-var positive-int */
            $info['dimension'] = substr_count($matches[7], '[');
        }

        $extra = substr($definition, strlen($matches[0]));

        return $info + $this->extraInfo($extra);
    }
}
