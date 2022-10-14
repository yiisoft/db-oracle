<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <a href="https://www.oracle.com/database/technologies/" target="_blank">
        <img src="https://avatars3.githubusercontent.com/u/4430336" height="100px">
    </a>
    <h1 align="center">Yii Framework Oracle Extension</h1>
    <br>
</p>

This extension provides the Oracle database support for the [Yii framework](http://www.yiiframework.com).

For license information check the [LICENSE](LICENSE.md)-file.

Documentation is at [docs/guide/README.md](docs/guide/README.md).

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db-oracle/v/stable.png)](https://packagist.org/packages/yiisoft/db-oracle)
[![Total Downloads](https://poser.pugx.org/yiisoft/db-oracle/downloads.png)](https://packagist.org/packages/yiisoft/db-oracle)
[![rector](https://github.com/yiisoft/db-oracle/actions/workflows/rector.yml/badge.svg)](https://github.com/yiisoft/db-oracle/actions/workflows/rector.yml)
[![codecov](https://codecov.io/gh/yiisoft/db-oracle/branch/dev/graph/badge.svg?token=XGJAFXVHSH)](https://codecov.io/gh/yiisoft/db-oracle)
[![StyleCI](https://github.styleci.io/repos/114756574/shield?branch=master)](https://github.styleci.io/repos/114756574?branch=master)



## Support version

|  PHP | Oracle Version           |  CI-Actions
|:----:|:------------------------:|:---:|
|**8.0 - 8.2**| **11G - 12G**|[![build](https://github.com/yiisoft/db-oracle/actions/workflows/build.yml/badge.svg?branch=dev)](https://github.com/yiisoft/db-oracle/actions/workflows/build.yml) [![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-oracle%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-oracle/master) [![static analysis](https://github.com/yiisoft/db-oracle/actions/workflows/static.yml/badge.svg?branch=dev)](https://github.com/yiisoft/db-oracle/actions/workflows/static.yml) [![type-coverage](https://shepherd.dev/github/yiisoft/db-oracle/coverage.svg)](https://shepherd.dev/github/yiisoft/db-oracle)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yiisoft/db-oracle
```

or add

```
"yiisoft/db-oracle": "~1.0.0"
```

to the require section of your composer.json.

## Configuration

Using yiisoft/composer-config-plugin automatically get the settings of `Yiisoft\Cache\CacheInterface::class`, `LoggerInterface::class`, and `Profiler::class`.

Di-Container:

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Oracle\Connection as OracleConnection;

return [
    ConnectionInterface::class => [
        'class' => OracleConnection::class,
        '__construct()' => [
            'dsn' => $params['yiisoft/db-oracle']['dsn'],
        ],
        'setUsername()' => [$params['yiisoft/db-oracle']['username']],
        'setPassword()' => [$params['yiisoft/db-oracle']['password']],
    ]
];
```

Params.php

```php
return [
    'yiisoft/db-oracle' => [
        'dsn' => 'oci:dbname=localhost/XE;charset=AL32UTF8;',
        'username' => 'system',
        'password' => 'oracle',
    ],
];
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

## License

The Yii Framework Oracle Extension is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
