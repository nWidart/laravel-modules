# Laravel-Modules

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nwidart/laravel-modules.svg?style=flat-square)](https://packagist.org/packages/nwidart/laravel-modules)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/nWidart/laravel-modules.svg?maxAge=86400&style=flat-square)](https://scrutinizer-ci.com/g/nWidart/laravel-modules/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/nwidart/laravel-modules.svg?style=flat-square)](https://packagist.org/packages/nwidart/laravel-modules)

| **Laravel** | **laravel-modules** |
|-------------|---------------------|
| 5.4         | ^1.0                |
| 5.5         | ^2.0                |
| 5.6         | ^3.0                |
| 5.7         | ^4.0                |
| 5.8         | ^5.0                |
| 6.0         | ^6.0                |
| 7.0         | ^7.0                |
| 8.0         | ^8.0                |
| 9.0         | ^9.0                |
| 10.0        | ^10.0               |
| 11.0        | ^11.0               |
| 12.0        | ^12.0               |
| 13.0        | ^13.0               |

`nwidart/laravel-modules` is a Laravel package created to manage your large Laravel app using modules. A Module is like a Laravel package, it has some views, controllers or models. This package is supported and tested in Laravel 11.

This package is a re-published, re-organised and maintained version of [pingpong/modules](https://github.com/pingpong-labs/modules), which isn't maintained anymore.

With one big bonus that the original package didn't have: **tests**.

## Install

To install via Composer, run:

``` bash
composer require nwidart/laravel-modules
```

The package will automatically register a service provider and alias.

Optionally, publish the package's configuration file by running:

``` bash
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"
```

### Autoloading

> from v11.0 autoloading `"Modules\\": "modules/",` is no longer required, and should be removed from your composer.json if present.

By default, the module classes are not loaded automatically. You can autoload your modules by adding merge-plugin to the extra section:

```json
"extra": {
    "laravel": {
        "dont-discover": []
    },
    "merge-plugin": {
        "include": [
            "Modules/*/composer.json"
        ]
    }
},
```

**Important**

on the first installation you will be asked:

```bash
Do you trust "wikimedia/composer-merge-plugin" to execute code and wish to enable it now? (writes "allow-plugins" to composer.json) [y,n,d,?]
```

Answer `y` to allow the plugin to be executed. Otherwise, you will need to manually enable the following to your composer.json:

```json
"config": {
    "allow-plugins": {
        "wikimedia/composer-merge-plugin": true
    }
```

> if `"wikimedia/composer-merge-plugin": false` modules will not be autoloaded.

**Tip: don't forget to run `composer dump-autoload` afterwards**

## Documentation

You'll find installation instructions and full documentation on [https://laravelmodules.com/](https://laravelmodules.com/docs).

## Community

We also have a Discord community. [https://discord.gg/hkF7BRvRZK](https://discord.gg/hkF7BRvRZK) For quick help, ask questions in the appropriate channel.

## Credits

- [Nicolas Widart](https://github.com/nwidart)
- [David Carr](https://github.com/dcblogdev)
- [gravitano](https://github.com/gravitano)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
