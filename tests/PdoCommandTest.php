<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Oracle\Tests\Provider\CommandPdoProvider;
use Yiisoft\Db\Oracle\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Common\CommonPdoCommandTest;

/**
 * @group oracle
 */
final class PdoCommandTest extends CommonPdoCommandTest
{
    use IntegrationTestTrait;

    #[DataProviderExternal(CommandPdoProvider::class, 'bindParam')]
    public function testBindParam(
        string $field,
        string $name,
        mixed $value,
        int $dataType,
        ?int $length,
        mixed $driverOptions,
        array $expected,
    ): void {
        parent::testBindParam($field, $name, $value, $dataType, $length, $driverOptions, $expected);
    }

    #[DataProviderExternal(CommandPdoProvider::class, 'bindParamsNonWhere')]
    public function testBindParamsNonWhere(string $sql): void
    {
        parent::testBindParamsNonWhere($sql);
    }

    public function testColumnCase(): void
    {
        $this->markTestSkipped('It must be implemented.');
    }
}
