<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\oracle;

/**
 * Database connection class prefilled for Oracle.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Connection extends \yii\db\Connection
{
    /**
     * {@inheritdoc}
     */
    public $schemaMap = [
        'oci' => Schema::class, // Oracle driver
    ];
}