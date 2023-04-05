<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Oracle\Dsn;

/**
 * @group oracle
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class DsnTest extends TestCase
{
    public function testAsStringWithDatabaseName(): void
    {
        $this->assertSame(
            'oci:dbname=localhost:1521;charset=AL32UTF8',
            (new Dsn('oci', 'localhost', port: '1521', options: ['charset' => 'AL32UTF8']))->asString(),
        );
    }

    public function testAsStringWithDatabaseNameWithEmptyString(): void
    {
        $this->assertSame(
            'oci:dbname=localhost:1521;charset=AL32UTF8',
            (new Dsn('oci', 'localhost', '', '1521', ['charset' => 'AL32UTF8']))->asString(),
        );
    }

    public function testAsStringWithDatabaseNameWithNull(): void
    {
        $this->assertSame(
            'oci:dbname=localhost:1521;charset=AL32UTF8',
            (new Dsn('oci', 'localhost', null, '1521', ['charset' => 'AL32UTF8']))->asString(),
        );
    }

    /**
     * Oracle service name it support only in version 18 and higher, for docker image gvenzl/oracle-xe:18
     */
    public function testAsStringWithService(): void
    {
        $this->assertSame(
            'oci:dbname=localhost:1521/yiitest;charset=AL32UTF8',
            (new Dsn('oci', 'localhost', 'yiitest', '1521', ['charset' => 'AL32UTF8']))->asString(),
        );
    }

    public function testAsStringWithSID(): void
    {
        $this->assertSame(
            'oci:dbname=localhost:1521/XE;charset=AL32UTF8',
            (new Dsn('oci', 'localhost', 'XE', '1521', ['charset' => 'AL32UTF8']))->asString(),
        );
    }
}
