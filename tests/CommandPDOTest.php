<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonCommandPDOTest;

/**
 * @group oracle
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CommandPDOTest extends CommonCommandPDOTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\CommandPDOProvider::bindParam
     */
    public function testBindParam(
        string $field,
        string $name,
        mixed $value,
        int $dataType,
        int|null $length,
        mixed $driverOptions,
        array $expected,
    ): void {
        parent::testBindParam($field, $name, $value, $dataType, $length, $driverOptions, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\CommandPDOProvider::bindParamsNonWhere
     */
    public function testBindParamsNonWhere(string $sql): void
    {
        parent::testBindParamsNonWhere($sql);
    }

    public function testColumnCase(): void
    {
        $this->markTestSkipped('It must be implemented.');
    }
}
