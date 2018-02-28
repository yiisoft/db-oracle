<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\oracle\data\ar;

/**
 * ActiveRecord is ...
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 1.0
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
    public static $db;

    public static function getDb()
    {
        return self::$db;
    }
}
