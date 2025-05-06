<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Provider;

use DateTime;
use DateTimeImmutable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Oracle\Column\BinaryColumn;
use Yiisoft\Db\Oracle\Column\BooleanColumn;
use Yiisoft\Db\Oracle\Column\DateTimeColumn;
use Yiisoft\Db\Oracle\Column\JsonColumn;
use Yiisoft\Db\Tests\Support\Stringable;

class ColumnProvider extends \Yiisoft\Db\Tests\Provider\ColumnProvider
{
    public static function predefinedTypes(): array
    {
        $values = parent::predefinedTypes();
        $values['binary'][0] = BinaryColumn::class;
        $values['boolean'][0] = BooleanColumn::class;
        $values['datetime'][0] = DateTimeColumn::class;
        $values['json'][0] = JsonColumn::class;

        return $values;
    }

    public static function dbTypecastColumns(): array
    {
        $values = parent::dbTypecastColumns();
        $values['binary'][0] = new BinaryColumn();
        $values['boolean'] = [
            new BooleanColumn(),
            [
                [null, null],
                [null, ''],
                ['1', true],
                ['1', 1],
                ['1', 1.0],
                ['1', '1'],
                ['0', false],
                ['0', 0],
                ['0', 0.0],
                ['0', '0'],
                [$expression = new Expression('expression'), $expression],
            ],
        ];
        $values['timestamp'][0] = new DateTimeColumn(ColumnType::TIMESTAMP, size: 0);
        $values['timestamp6'][0] = new DateTimeColumn(ColumnType::TIMESTAMP, size: 6);
        $values['datetime'][0] = new DateTimeColumn(size: 0);
        $values['datetime6'][0] = new DateTimeColumn(size: 6);
        $values['datetimetz'][0] = new DateTimeColumn(ColumnType::DATETIMETZ, size: 0);
        $values['datetimetz6'][0] = new DateTimeColumn(ColumnType::DATETIMETZ, size: 6);
        $values['time'][0] = new DateTimeColumn(ColumnType::TIME, size: 0);
        $values['time6'][0] = new DateTimeColumn(ColumnType::TIME, size: 6);
        $values['timetz'][0] = new DateTimeColumn(ColumnType::TIMETZ, size: 0);
        $values['timetz6'][0] = new DateTimeColumn(ColumnType::TIMETZ, size: 6);
        $values['date'][0] = new DateTimeColumn(ColumnType::DATE);
        $values['json'][0] = new JsonColumn();

        $values['timetz'] = [
            new DateTimeColumn(ColumnType::TIMETZ, size: 0),
            [
                [null, null],
                [null, ''],
                [new Expression("INTERVAL '0 00:00:00' DAY(0) TO SECOND(0)"), '2025-04-19'],
                [new Expression("INTERVAL '0 14:11:35' DAY(0) TO SECOND(0)"), '14:11:35'],
                [new Expression("INTERVAL '0 14:11:35' DAY(0) TO SECOND(0)"), '14:11:35.123456'],
                [new Expression("INTERVAL '0 12:11:35' DAY(0) TO SECOND(0)"), '14:11:35 +02:00'],
                [new Expression("INTERVAL '0 12:11:35' DAY(0) TO SECOND(0)"), '14:11:35.123456 +02:00'],
                [new Expression("INTERVAL '0 14:11:35' DAY(0) TO SECOND(0)"), '2025-04-19 14:11:35'],
                [new Expression("INTERVAL '0 14:11:35' DAY(0) TO SECOND(0)"), '2025-04-19 14:11:35.123456'],
                [new Expression("INTERVAL '0 12:11:35' DAY(0) TO SECOND(0)"), '2025-04-19 14:11:35 +02:00'],
                [new Expression("INTERVAL '0 12:11:35' DAY(0) TO SECOND(0)"), '2025-04-19 14:11:35.123456 +02:00'],
                [new Expression("INTERVAL '0 14:11:35' DAY(0) TO SECOND(0)"), '1745071895'],
                [new Expression("INTERVAL '0 14:11:35' DAY(0) TO SECOND(0)"), '1745071895.123'],
                [new Expression("INTERVAL '0 14:11:35' DAY(0) TO SECOND(0)"), 1745071895],
                [new Expression("INTERVAL '0 14:11:35' DAY(0) TO SECOND(0)"), 1745071895.123],
                [new Expression("INTERVAL '0 14:11:35' DAY(0) TO SECOND(0)"), 51095],
                [new Expression("INTERVAL '0 14:11:35' DAY(0) TO SECOND(0)"), 51095.123456],
                [new Expression("INTERVAL '0 12:11:35' DAY(0) TO SECOND(0)"), new DateTimeImmutable('14:11:35 +02:00')],
                [new Expression("INTERVAL '0 12:11:35' DAY(0) TO SECOND(0)"), new DateTime('14:11:35 +02:00')],
                [new Expression("INTERVAL '0 12:11:35' DAY(0) TO SECOND(0)"), new Stringable('14:11:35 +02:00')],
                [$expression = new Expression("INTERVAL '0 14:11:35' DAY(0) TO SECOND(0)"), $expression],
            ],
        ];
        $values['timetz6'] = [
            new DateTimeColumn(ColumnType::TIMETZ, size: 6),
            [
                [null, null],
                [null, ''],
                [new Expression("INTERVAL '0 00:00:00.000000' DAY(0) TO SECOND(6)"), '2025-04-19'],
                [new Expression("INTERVAL '0 14:11:35.000000' DAY(0) TO SECOND(6)"), '14:11:35'],
                [new Expression("INTERVAL '0 14:11:35.123456' DAY(0) TO SECOND(6)"), '14:11:35.123456'],
                [new Expression("INTERVAL '0 12:11:35.000000' DAY(0) TO SECOND(6)"), '14:11:35 +02:00'],
                [new Expression("INTERVAL '0 12:11:35.123456' DAY(0) TO SECOND(6)"), '14:11:35.123456 +02:00'],
                [new Expression("INTERVAL '0 14:11:35.000000' DAY(0) TO SECOND(6)"), '2025-04-19 14:11:35'],
                [new Expression("INTERVAL '0 14:11:35.123456' DAY(0) TO SECOND(6)"), '2025-04-19 14:11:35.123456'],
                [new Expression("INTERVAL '0 12:11:35.000000' DAY(0) TO SECOND(6)"), '2025-04-19 14:11:35 +02:00'],
                [new Expression("INTERVAL '0 12:11:35.123456' DAY(0) TO SECOND(6)"), '2025-04-19 14:11:35.123456 +02:00'],
                [new Expression("INTERVAL '0 14:11:35.000000' DAY(0) TO SECOND(6)"), '1745071895'],
                [new Expression("INTERVAL '0 14:11:35.123000' DAY(0) TO SECOND(6)"), '1745071895.123'],
                [new Expression("INTERVAL '0 14:11:35.000000' DAY(0) TO SECOND(6)"), 1745071895],
                [new Expression("INTERVAL '0 14:11:35.123000' DAY(0) TO SECOND(6)"), 1745071895.123],
                [new Expression("INTERVAL '0 14:11:35.000000' DAY(0) TO SECOND(6)"), 51095],
                [new Expression("INTERVAL '0 14:11:35.123456' DAY(0) TO SECOND(6)"), 51095.123456],
                [new Expression("INTERVAL '0 12:11:35.123456' DAY(0) TO SECOND(6)"), new DateTimeImmutable('14:11:35.123456 +02:00')],
                [new Expression("INTERVAL '0 12:11:35.123456' DAY(0) TO SECOND(6)"), new DateTime('14:11:35.123456 +02:00')],
                [new Expression("INTERVAL '0 12:11:35.123456' DAY(0) TO SECOND(6)"), new Stringable('14:11:35.123456 +02:00')],
                [$expression = new Expression("INTERVAL '0 14:11:35.123456' DAY(0) TO SECOND(6)"), $expression],
            ],
        ];

        foreach (['timestamp', 'timestamp6', 'datetime', 'datetime6', 'datetimetz', 'datetimetz6'] as $key) {
            foreach ($values[$key][1] as &$value) {
                if (is_string($value[0])) {
                    $value[0] = new Expression("TIMESTAMP '$value[0]'");
                }
            }
            unset($value);
        }

        foreach ($values['time'][1] as &$value) {
            if (is_string($value[0])) {
                $value[0] = new Expression("INTERVAL '0 $value[0]' DAY(0) TO SECOND(0)");
            }
        }
        unset($value);

        foreach ($values['time6'][1] as &$value) {
            if (is_string($value[0])) {
                $value[0] = new Expression("INTERVAL '0 $value[0]' DAY(0) TO SECOND(6)");
            }
        }
        unset($value);

        foreach ($values['date'][1] as &$value) {
            if (is_string($value[0])) {
                $value[0] = new Expression("DATE '$value[0]'");
            }
        }

        return $values;
    }

    public static function phpTypecastColumns(): array
    {
        $values = parent::phpTypecastColumns();
        $values['binary'][0] = new BinaryColumn();
        $values['boolean'][0] = new BooleanColumn();
        $values['timestamp'][0] = new DateTimeColumn(ColumnType::TIMESTAMP);
        $values['datetime'][0] = new DateTimeColumn();
        $values['datetimetz'][0] = new DateTimeColumn(ColumnType::DATETIMETZ);
        $values['time'][0] = new DateTimeColumn(ColumnType::TIME);
        $values['timetz'][0] = new DateTimeColumn(ColumnType::TIMETZ);
        $values['date'][0] = new DateTimeColumn(ColumnType::DATE);
        $values['json'][0] = new JsonColumn();

        return $values;
    }
}
