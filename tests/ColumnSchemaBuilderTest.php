<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Db\Oracle\Tests;

use Yiisoft\Db\oci\ColumnSchemaBuilder;
use Yiisoft\Db\Schema;

/**
 * ColumnSchemaBuilderTest tests ColumnSchemaBuilder for Oracle.
 */
class ColumnSchemaBuilderTest extends DatabaseTestCase
{
    /**
     * @param string $type
     * @param int $length
     * @return ColumnSchemaBuilder
     */
    public function getColumnSchemaBuilder($type, $length = null)
    {
        return new ColumnSchemaBuilder($type, $length, $this->getConnection());
    }

    /**
     * @return array
     */
    public function typesProvider()
    {
        return [
            ['integer UNSIGNED', Schema::TYPE_INTEGER, null, [
                ['unsigned'],
            ]],
            ['integer(10) UNSIGNED', Schema::TYPE_INTEGER, 10, [
                ['unsigned'],
            ]],
        ];
    }

    /**
     * @dataProvider typesProvider
     * @param string $expected
     * @param string $type
     * @param int|null $length
     * @param mixed $calls
     */
    public function testCustomTypes($expected, $type, $length, $calls)
    {
        $this->checkBuildString($expected, $type, $length, $calls);
    }

    /**
     * @param string $expected
     * @param string $type
     * @param int|null $length
     * @param array $calls
     */
    public function checkBuildString($expected, $type, $length, $calls)
    {
        $builder = $this->getColumnSchemaBuilder($type, $length);
        foreach ($calls as $call) {
            $method = array_shift($call);
            \call_user_func_array([$builder, $method], $call);
        }

        self::assertEquals($expected, $builder->__toString());
    }
}
