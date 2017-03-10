# Laravel-Modules

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nwidart/laravel-modules.svg?style=flat-square)](https://packagist.org/packages/nwidart/laravel-modules)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/nWidart/laravel-modules/master.svg?style=flat-square)](https://travis-ci.org/nWidart/laravel-modules)
[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/nWidart/laravel-modules.svg?maxAge=86400&style=flat-square)](https://scrutinizer-ci.com/g/nWidart/laravel-modules/?branch=master)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/25320a08-8af4-475e-a23e-3321f55bf8d2.svg?style=flat-square)](https://insight.sensiolabs.com/projects/25320a08-8af4-475e-a23e-3321f55bf8d2)
[![Quality Score](https://img.shields.io/scrutinizer/g/nWidart/laravel-modules.svg?style=flat-square)](https://scrutinizer-ci.com/g/nWidart/laravel-modules)
[![Total Downloads](https://img.shields.io/packagist/dt/nwidart/laravel-modules.svg?style=flat-square)](https://packagist.org/packages/nwidart/laravel-modules)


`nwidart/laravel-modules` is a laravel package which created to manage your large laravel app using modules. Module is like a laravel package, it has some views, controllers or models. This package is supported and tested in Laravel 5.

This package is a re-published, re-organised and maintained version of [pingpong/modules](https://github.com/pingpong-labs/modules), which isn't maintained anymore. This package is used in [AsgardCMS](https://asgardcms.com/).

With one big added bonus that the original package didn't have: **tests**.

Find out why you should use this package in the article: [Writing modular applications with laravel-modules](https://nicolaswidart.com/blog/writing-modular-applications-with-laravel-modules).

## Install

To install through composer, by run the following command:

``` bash
composer require nwidart/laravel-modules
```

### Add Service Provider

Next add the following service provider in `config/app.php`.

``` php
'providers' => [
  Nwidart\Modules\LaravelModulesServiceProvider::class,
],
```

Next, add the following aliases to `aliases` array in the same file.

``` php
'aliases' => [
  'Module' => Nwidart\Modules\Facades\Module::class,
],
```

Next publish the package's configuration file by running :

``` bash
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"
```

### Autoloading

By default the module classes are not loaded automatically. You can autoload your modules using `psr-4`. For example :

``` json
{
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "Modules\\": "Modules/"
    }
  }
}
```

## Documentation

You'll find installation instructions and full documentation on [https://nwidart.com/laravel-modules/](https://nwidart.com/laravel-modules/).

## Credits

- [Nicolas Widart](https://github.com/nwidart)
- [gravitano](https://github.com/gravitano)
- [All Contributors](../../contributors)

## About Nicolas Widart

Nicolas Widart is a freelance web developer specialising on the laravel framework. View all my packages [on my website](https://nicolaswidart.com/projects).


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
