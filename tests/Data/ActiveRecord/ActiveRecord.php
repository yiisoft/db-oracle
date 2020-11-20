<?php

declare(strict_types=1);
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Db\Oracle\Tests\Data\ActiveRecord;

/**
 * ActiveRecord is ...
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 1.0
 */
class ActiveRecord extends \Yiisoft\Db\ActiveRecord
{
    public static $db;

    public static function getDb()
    {
        return self::$db;
    }
}
