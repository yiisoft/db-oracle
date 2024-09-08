<?php

declare(strict_types=1);

use Yiisoft\Db\Oracle\Column\ColumnFactory;
use Yiisoft\Db\Oracle\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractColumnBuilderTest;

/**
 * @group oracle
 */
class ColumnBuilderTest extends AbstractColumnBuilderTest
{
    use TestTrait;

    public function testColumnFactory(): void
    {
        $db = $this->getConnection();
        $columnBuilderClass = $db->getColumnBuilderClass();

        $this->assertInstanceOf(ColumnFactory::class, $columnBuilderClass::columnFactory());
    }
}
