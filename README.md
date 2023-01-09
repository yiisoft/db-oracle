<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <a href="https://www.oracle.com/database/technologies/" target="_blank">
        <img src="https://avatars3.githubusercontent.com/u/4430336" height="100px">
    </a>
    <h1 align="center">Yii Database Oracle Extension</h1>
    <br>
</p>

Yii Database Oracle Extension is a database driver for [Oracle] databases that is part of the [YiiFramework]. The Yii framework is an open-source PHP framework for web application development.

Yii Database Oracle Extension allows you to connect to [Oracle] databases from your Yii application and perform various database operations such as executing queries, creating and modifying database schema, and processing data. It supports a wide range of [Oracle] versions and provides a simple and efficient interface for working with [Oracle] databases in your Yii application.

To use Yii Database Oracle Extension in your Yii application, you need to have the [Oracle] client library installed and configured on your server, and you need to specify the correct database connection parameters in your Yii application's configuration file. Once you have done this, you can use the Yii Database Oracle Extension driver to connect to your Oracle database and perform various database operations as needed.

It is used in [YiiFramework] but can be used separately.

[Oracle]: https://www.oracle.com/database/technologies/
[YiiFramework]: https://www.yiiframework.com/

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db-oracle/v/stable.png)](https://packagist.org/packages/yiisoft/db-oracle)
[![Total Downloads](https://poser.pugx.org/yiisoft/db-oracle/downloads.png)](https://packagist.org/packages/yiisoft/db-oracle)
[![rector](https://github.com/yiisoft/db-oracle/actions/workflows/rector.yml/badge.svg)](https://github.com/yiisoft/db-oracle/actions/workflows/rector.yml)
[![codecov](https://codecov.io/gh/yiisoft/db-oracle/branch/master/graph/badge.svg?token=XGJAFXVHSH)](https://codecov.io/gh/yiisoft/db-oracle)
[![StyleCI](https://github.styleci.io/repos/114756574/shield?branch=master)](https://github.styleci.io/repos/114756574?branch=master)

### Support version

|  PHP | Oracle Version           |  CI-Actions
|:----:|:------------------------:|:---:|
|**8.0 - 8.2**| **11 - 21**|[![build](https://github.com/yiisoft/db-oracle/actions/workflows/build.yml/badge.svg?branch=dev)](https://github.com/yiisoft/db-oracle/actions/workflows/build.yml) [![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-oracle%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-oracle/master) [![static analysis](https://github.com/yiisoft/db-oracle/actions/workflows/static.yml/badge.svg?branch=dev)](https://github.com/yiisoft/db-oracle/actions/workflows/static.yml) [![type-coverage](https://shepherd.dev/github/yiisoft/db-oracle/coverage.svg)](https://shepherd.dev/github/yiisoft/db-oracle)

### Installation

The package could be installed via composer:

```php
composer require yiisoft/db-oracle
```

### Config with [YiiFramework]

The configuration with [container di](https://github.com/yiisoft/di) of [YiiFramework].

Also you can use any container di which implements [PSR-11](https://www.php-fig.org/psr/psr-11/).

db.php

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Oracle\ConnectionPDO;
use Yiisoft\Db\Oracle\PDODriver;

/** @var array $params */

return [
    ConnectionInterface::class => [
        'class' => ConnectionPDO::class,
        '__construct()' => [
            'driver' => new PDODriver($params['yiisoft/db-oracle']['dsn']),
        ]
    ]
];
```

params.php

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Oracle\Dsn;

return [
    'yiisoft/db-oracle' => [
        'dsn' => (new Dsn('oci', 'localhost', 'XE', '1521', ['charset' => 'AL32UTF8']))->asString(),
    ]
];
```

### Config without [YiiFramework]

```php
<?php

declare(strict_types=1);

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Oracle\ConnectionPDO;
use Yiisoft\Db\Oracle\Dsn;
use Yiisoft\Db\Oracle\PDODriver;

// Or any other PSR-16 cache implementation.
$arrayCache = new ArrayCache();

// Or any other PSR-6 cache implementation.
$cache = new Cache($arrayCache); 
$dsn = (new Dsn('oci', 'localhost', 'XE', '1521', ['charset' => 'AL32UTF8']))->asString();

// Or any other PDO driver.
$pdoDriver = new PDODriver($dsn); 
$schemaCache = new SchemaCache($cache);
$db = new ConnectionPDO($pdoDriver, $schemaCache);
```

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```shell
./vendor/bin/infection
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

### Rector

Use [Rector](https://github.com/rectorphp/rector) to make codebase follow some specific rules or 
use either newest or any specific version of PHP: 

```shell
./vendor/bin/rector
```

### Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

### Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)

### License

The Yii Framework Oracle Extension is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
