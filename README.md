<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <a href="https://www.oracle.com/database/technologies/" target="_blank">
        <img src="https://avatars3.githubusercontent.com/u/4430336" height="80px">
    </a>
    <h1 align="center">Oracle driver for Yii Database</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/db-oracle/v/stable.png)](https://packagist.org/packages/yiisoft/db-oracle)
[![Total Downloads](https://poser.pugx.org/yiisoft/db-oracle/downloads.png)](https://packagist.org/packages/yiisoft/db-oracle)
[![rector](https://github.com/yiisoft/db-oracle/actions/workflows/rector.yml/badge.svg)](https://github.com/yiisoft/db-oracle/actions/workflows/rector.yml)
[![codecov](https://codecov.io/gh/yiisoft/db-oracle/branch/master/graph/badge.svg?token=XGJAFXVHSH)](https://codecov.io/gh/yiisoft/db-oracle)
[![StyleCI](https://github.styleci.io/repos/114756574/shield?branch=master)](https://github.styleci.io/repos/114756574?branch=master)

Oracle driver for [Yii Database](https://github.com/yiisoft/db) is a database driver for [Oracle] databases.

The package allows you to connect to [Oracle] databases from your application and perform various database operations
such as executing queries, creating and modifying database schema, and processing data. It supports a wide range of
[Oracle] versions and provides a simple and efficient interface for working with [Oracle] databases.

To use the package, you need to have the [Oracle] client library installed and configured on your server, and you need
to specify the correct database connection parameters. Once you have done this, you can use the driver to connect to
your Oracle database and perform various database operations as needed.

[Oracle]: https://www.oracle.com/database/technologies/

## Support version

| PHP | Oracle Version | CI-Actions
|----|------------------------|---|
|**8.0 - 8.2**| **12c - 21c**|[![build](https://github.com/yiisoft/db-oracle/actions/workflows/build.yml/badge.svg?branch=dev)](https://github.com/yiisoft/db-oracle/actions/workflows/build.yml) [![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fdb-oracle%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/db-oracle/master) [![static analysis](https://github.com/yiisoft/db-oracle/actions/workflows/static.yml/badge.svg?branch=dev)](https://github.com/yiisoft/db-oracle/actions/workflows/static.yml) [![type-coverage](https://shepherd.dev/github/yiisoft/db-oracle/coverage.svg)](https://shepherd.dev/github/yiisoft/db-oracle)

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```php
composer require yiisoft/db-oracle
```

## Documentation

- For config connection to Oracle database check [Connecting Oracle](https://github.com/yiisoft/db/blob/master/docs/en/connection/oracle.md).
- [Check the documentation docs](https://github.com/yiisoft/db/blob/master/docs/en/README.md) to learn about usage.
- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place
for that. You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Framework Oracle Extension is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
