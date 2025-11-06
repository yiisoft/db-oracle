<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle;

use Yiisoft\Db\Syntax\AbstractSqlParser;

final class SqlParser extends AbstractSqlParser
{
    public function getNextPlaceholder(?int &$position = null): ?string
    {
        $result = null;
        $length = $this->length - 1;

        while ($this->position < $length) {
            $pos = $this->position++;

            match ($this->sql[$pos]) {
                ':' => ($word = $this->parseWord()) === ''
                    ? $this->skipChars(':')
                    : $result = ':' . $word,
                '"' => $this->skipToAfterChar('"'),
                "'" => $this->skipQuotedWithoutEscape($this->sql[$pos]),
                'q', 'Q' => $this->sql[$this->position] === "'"
                    ? $this->skipQuotedWithQ()
                    : null,
                '-' => $this->sql[$this->position] === '-'
                    ? ++$this->position && $this->skipToAfterChar("\n")
                    : null,
                '/' => $this->sql[$this->position] === '*'
                    ? ++$this->position && $this->skipToAfterString('*/')
                    : null,
                default => null,
            };

            if ($result !== null) {
                $position = $pos;

                return $result;
            }
        }

        return null;
    }

    /**
     * Skips quoted string with Q-operator.
     */
    private function skipQuotedWithQ(): void
    {
        $endChar = match ($this->sql[++$this->position]) {
            '[' => ']',
            '<' => '>',
            '{' => '}',
            '(' => ')',
            default => $this->sql[$this->position],
        };

        ++$this->position;

        $this->skipToAfterString("$endChar'");
    }
}
