<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Support;

final class OracleTestHelper
{
    public static function changeSqlForBatchInsert(string &$str, array $expectedParams = []): void
    {
        if (empty($str)) {
            return;
        }

        $str = str_replace(
            ' VALUES (',
            "\nSELECT ",
            str_replace(
                '), (',
                " FROM DUAL UNION ALL\nSELECT ",
                substr($str, 0, -1),
            ),
        ) . ' FROM DUAL';

        foreach ($expectedParams as $param => $value) {
            $str = match ($value) {
                '1' => preg_replace('/\bTRUE\b/i', $param, $str, 1),
                '0' => preg_replace('/\bFALSE\b/i', $param, $str, 1),
                default => $str,
            };
        }
    }
}
