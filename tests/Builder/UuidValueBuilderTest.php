<?php

declare(strict_types=1);

namespace Yiisoft\Db\Oracle\Tests\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Db\Expression\Value\UuidValue;
use Yiisoft\Db\Helper\DbUuidHelper;
use Yiisoft\Db\Oracle\Builder\UuidValueBuilder;
use Yiisoft\Db\Oracle\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

use function hex2bin;
use function strtolower;

/**
 * @group oracle
 */
final class UuidValueBuilderTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    private const UUID = '738146be-87b1-49f2-9913-36142fb6fcbe';
    private const HEX = '738146be87b149f2991336142fb6fcbe';

    /**
     * Every form {@see UuidValue} accepts, all denoting the same UUID.
     */
    public static function values(): iterable
    {
        yield 'canonical' => [self::UUID];
        yield 'canonical in upper case' => ['738146BE-87B1-49F2-9913-36142FB6FCBE'];
        yield 'hexadecimal' => [self::HEX];
        yield 'hexadecimal in upper case' => ['738146BE87B149F2991336142FB6FCBE'];
        yield 'bytes' => [hex2bin(self::HEX)];
    }

    /**
     * `UuidValue` normalizes the value to the canonical form on construction, so the literal holds the same 32
     * hexadecimal characters whichever form it was created from.
     */
    #[DataProvider('values')]
    public function testBuildEmitsHexToRawLiteral(string $value): void
    {
        $builder = new UuidValueBuilder();

        $params = [];
        $result = $builder->build(new UuidValue($value), $params);

        $this->assertSame("HEXTORAW('" . self::HEX . "')", $result);
        $this->assertSame([], $params);
    }

    #[DataProvider('values')]
    public function testInsertAndSelectUuid(string $value): void
    {
        $db = $this->getSharedConnection();

        $this->dropTable('uuid_value');
        $this->executeStatements('CREATE TABLE [[uuid_value]] ([[id]] raw(16) NOT NULL)');

        $db->createCommand()->insert('uuid_value', ['id' => new UuidValue($value)])->execute();

        // Depending on the Oracle version a `raw` column reads back as 16 raw bytes or as 32 hexadecimal characters,
        // uppercase in the latter case. `DbUuidHelper::toUuid()` accepts both but keeps the case it was given.
        $stored = $db->createCommand('SELECT [[id]] FROM [[uuid_value]]')->queryScalar();

        $this->assertIsString($stored);
        $this->assertSame(self::UUID, strtolower(DbUuidHelper::toUuid($stored)));

        $this->dropTable('uuid_value');
    }
}
