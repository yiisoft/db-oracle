<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Support;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Oracle\Connection;
use Yiisoft\Db\Oracle\Tests\Support\Fixture\FixtureDump;
use Yiisoft\Db\Tests\Support\TestHelper;

trait IntegrationTestTrait
{
    protected function createConnection(): Connection
    {
        return new Connection(
            TestConnection::createDriver(),
            TestHelper::createMemorySchemaCache(),
        );
    }

    protected function getDefaultFixture(): string
    {
        return FixtureDump::DEFAULT;
    }

    protected function replaceQuotes(string $sql): string
    {
        return str_replace(['[[', ']]'], '"', $sql);
    }

    protected function parseDump(string $content): array
    {
        [$drops, $creates] = explode('/* STATEMENTS */', $content, 2);
        [$statements, $triggers, $data] = explode('/* TRIGGERS */', $creates, 3);
        return array_merge(
            explode('--', $drops),
            explode(';', $statements),
            explode('/', $triggers),
            explode(';', $data),
        );
    }

    protected function dropView(ConnectionInterface $db, string $view): void
    {
        $db->createCommand('DROP VIEW ' . $db->getQuoter()->quoteTableName($view))->execute();
    }
}
