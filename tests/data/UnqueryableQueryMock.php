<?php

declare(strict_types=1);
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Db\Oracle\Tests\Data;

use yii\base\InvalidCallException;
use Yiisoft\Db\Query;

class UnqueryableQueryMock extends Query
{
    /**
     * {@inheritdoc}
     */
    public function one($db = null)
    {
        throw new InvalidCallException();
    }

    /**
     * {@inheritdoc}
     */
    public function all($db = null)
    {
        throw new InvalidCallException();
    }
}
