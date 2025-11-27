<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Oracle\Dsn;

/**
 * @group oracle
 */
final class DsnTest extends TestCase
{
    public function testConstruct(): void
    {
        $dsn = new Dsn('oci', 'localhost', 'yiitest', '1522', ['charset' => 'AL32UTF8']);

        $this->assertSame('oci', $dsn->driver);
        $this->assertSame('localhost', $dsn->host);
        $this->assertSame('yiitest', $dsn->databaseName);
        $this->assertSame('1522', $dsn->port);
        $this->assertSame(['charset' => 'AL32UTF8'], $dsn->options);
        $this->assertSame('oci:dbname=localhost:1522/yiitest;charset=AL32UTF8', (string) $dsn);
    }

    public function testConstructDefaults(): void
    {
        $dsn = new Dsn();

        $this->assertSame('oci', $dsn->driver);
        $this->assertSame('127.0.0.1', $dsn->host);
        $this->assertSame('', $dsn->databaseName);
        $this->assertSame('1521', $dsn->port);
        $this->assertSame([], $dsn->options);
        $this->assertSame('oci:dbname=127.0.0.1:1521', (string) $dsn);
    }

    public function testConstructWithEmptyPort(): void
    {
        $dsn = new Dsn('oci', 'localhost', port: '');

        $this->assertSame('oci', $dsn->driver);
        $this->assertSame('localhost', $dsn->host);
        $this->assertSame('', $dsn->databaseName);
        $this->assertSame('', $dsn->port);
        $this->assertSame([], $dsn->options);
        $this->assertSame('oci:dbname=localhost:', (string) $dsn);
    }
}
