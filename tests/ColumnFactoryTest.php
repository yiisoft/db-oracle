<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractColumnFactoryTest;

/**
 * @group oracle
 */
final class ColumnFactoryTest extends AbstractColumnFactoryTest
{
    use TestTrait;

    /** @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\ColumnFactoryProvider::dbTypes */
    public function testFromDbType(string $dbType, string $expectedType, string $expectedInstanceOf): void
    {
        parent::testFromDbType($dbType, $expectedType, $expectedInstanceOf);
    }

    /** @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\ColumnFactoryProvider::definitions */
    public function testFromDefinition(
        string $definition,
        string $expectedType,
        string $expectedInstanceOf,
        array $expectedMethodResults = []
    ): void {
        parent::testFromDefinition($definition, $expectedType, $expectedInstanceOf, $expectedMethodResults);
    }

    /** @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\ColumnFactoryProvider::pseudoTypes */
    public function testFromPseudoType(
        string $pseudoType,
        string $expectedType,
        string $expectedInstanceOf,
        array $expectedMethodResults = []
    ): void {
        parent::testFromPseudoType($pseudoType, $expectedType, $expectedInstanceOf, $expectedMethodResults);
    }

    /** @dataProvider \Yiisoft\Db\Oracle\Tests\Provider\ColumnFactoryProvider::types */
    public function testFromType(string $type, string $expectedType, string $expectedInstanceOf): void
    {
        parent::testFromType($type, $expectedType, $expectedInstanceOf);
    }
}
