# Laravel-Modules

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nwidart/laravel-modules.svg?style=flat-square)](https://packagist.org/packages/nwidart/laravel-modules)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/nWidart/laravel-modules/master.svg?style=flat-square)](https://travis-ci.org/nWidart/laravel-modules)
[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/nWidart/laravel-modules.svg?maxAge=2592000&style=flat-square)](https://scrutinizer-ci.com/g/nWidart/laravel-modules/?branch=master)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/25320a08-8af4-475e-a23e-3321f55bf8d2.svg?style=flat-square)](https://insight.sensiolabs.com/projects/25320a08-8af4-475e-a23e-3321f55bf8d2)
[![Quality Score](https://img.shields.io/scrutinizer/g/nWidart/laravel-modules.svg?style=flat-square)](https://scrutinizer-ci.com/g/nWidart/laravel-modules)
[![Total Downloads](https://img.shields.io/packagist/dt/nwidart/laravel-modules.svg?style=flat-square)](https://packagist.org/packages/nwidart/laravel-modules)


- [Upgrade Guide](#upgrade-guide)
- [Installation](#installation)
- [Configuration](#configuration)
- [Naming Convension](#naming-convension)
- [Folder Structure](#folder-structure)
- [Creating Module](#creating-a-module)
- [Artisan Commands](#artisan-commands)
- [Facades](#facades)
- [Entity](#entity)
- [Auto Scan Vendor Directory](#auto-scan-vendor-directory)
- [Publishing Modules](#publishing-modules)

`nwidart/laravel-modules` is a laravel package which created to manage your large laravel app using modules. Module is like a laravel package, it has some views, controllers or models. This package is supported and tested in Laravel 5.

This package is a re-published, re-organised and maintained version of [pingpong/modules](https://github.com/pingpong-labs/modules), which isn't maintained anymore. This package is used in [AsgardCMS](https://asgardcms.com/).

With one big added bonus that the original package didn't have: **tests**.

Find out why you should use this package in the article: [Writing modular applications with laravel-modules](https://nicolaswidart.com/blog/writing-modular-applications-with-laravel-modules).

<a name="upgrade-guide"></a>
## Upgrade Guide

<a name="installation"></a>
## Installation

### Quick

To install through composer, simply run the following command:

``` bash
composer require nwidart/laravel-modules
```

#### Add Service Provider

Next add the following service provider in `config/app.php`.

```php
'providers' => [
  Nwidart\Modules\LaravelModulesServiceProvider::class,
],
```

Next, add the following aliases to `aliases` array in the same file.

```php
'aliases' => [
  'Module' => Nwidart\Modules\Facades\Module::class,
],
```

Next publish the package's configuration file by running :

```
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"
```

#### Autoloading

By default controllers, entities or repositories not loaded automatically. You can autoload all that stuff using `psr-4`. For example :

```json
{
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "Modules\\": "modules/"
    }
  }
}
```

<a name="configuration"></a>
## Configuration

- `modules` - Used for save the generated modules.
- `assets` - Used for save the modules's assets from each modules.
- `migration` - Used for save the modules's migrations if you publish the modules's migrations.
- `generator` - Used for generate modules folders.
- `scan` - Used for allow to scan other folders.
- `enabled` - If `true`, the package will scan other paths. By default the value is `false`
- `paths` - The list of path which can scanned automatically by the package.
- `composer`
- `vendor` - Composer vendor name.
- `author.name` - Composer author name.
- `author.email` - Composer author email.
- `cache`
- `enabled` - If `true`, the scanned modules (all modules) will cached automatically. By default the value is `false`
- `key` - The name of cache.
- `lifetime` - Lifetime of cache.

<a name="creating-a-module"></a>
## Creating A Module

To create a new module you can simply run :

```
php artisan module:make <module-name>
```

- `<module-name>` - Required. The name of module will be created.

**Create a new module**

```
php artisan module:make Blog
```

**Create multiple modules**

```
php artisan module:make Blog User Auth
```

By default if you create a new module, that will add some resources like controller, seed class or provider automatically. If you don't want these, you can add `--plain` flag, to generate a plain module.

```shell
php artisan module:make Blog --plain
#OR
php artisan module:make Blog -p
```

<a name="naming-convension"></a>
**Naming Convension**

Because we are autoloading the modules using `psr-4`, we strongly recommend using `StudlyCase` convension.

<a name="folder-structure"></a>
**Folder Structure**

```
laravel-app/
app/
bootstrap/
vendor/
modules/
  ├── Blog/
      ├── Assets/
      ├── Config/
      ├── Console/
      ├── Database/
          ├── Migrations/
          ├── Seeders/
      ├── Entities/
      ├── Http/
          ├── Controllers/
          ├── Middleware/
          ├── Requests/
          ├── routes.php
      ├── Providers/
          ├── BlogServiceProvider.php
      ├── Resources/
          ├── lang/
          ├── views/
      ├── Repositories/
      ├── Tests/
      ├── composer.json
      ├── module.json
      ├── start.php
```

<a name="artisan-commands"></a>
## Artisan Commands

Create new module.

```
php artisan module:make blog
```

Use the specified module.

```php
php artisan module:use blog
```

Show all modules in command line.

```
php artisan module:list
```

Create new command for the specified module.

```
php artisan module:make-command CustomCommand blog

php artisan module:make-command CustomCommand --command=custom:command blog

php artisan module:make-command CustomCommand --namespace=Modules\Blog\Commands blog
```

Create new migration for the specified module.

```
php artisan module:make-migration create_users_table blog

php artisan module:make-migration create_users_table --fields="username:string, password:string" blog

php artisan module:make-migration add_email_to_users_table --fields="email:string:unique" blog

php artisan module:make-migration remove_email_from_users_table --fields="email:string:unique" blog

php artisan module:make-migration drop_users_table blog
```

Rollback, Reset and Refresh The Modules Migrations.

```
php artisan module:migrate-rollback

php artisan module:migrate-reset

php artisan module:migrate-refresh
```

Rollback, Reset and Refresh The Migrations for the specified module.

```
php artisan module:migrate-rollback blog

php artisan module:migrate-reset blog

php artisan module:migrate-refresh blog
```

Create new seed for the specified module.

```
php artisan module:make-seed users blog
```

Migrate from the specified module.

```
php artisan module:migrate blog
```

Migrate from all modules.

```
php artisan module:migrate
```

Seed from the specified module.

```
php artisan module:seed blog
```

Seed from all modules.

```
php artisan module:seed
```

Create new controller for the specified module.

```
php artisan module:make-controller SiteController blog
```

Publish assets from the specified module to public directory.

```
php artisan module:publish blog
```

Publish assets from all modules to public directory.

```
php artisan module:publish
```

Create new model for the specified module.

```
php artisan module:make-model User blog

php artisan module:make-model User blog --fillable="username,email,password"
```

Create new service provider for the specified module.

```
php artisan module:make-provider MyServiceProvider blog
```

Publish migration for the specified module or for all modules.

This helpful when you want to rollback the migrations. You can also run `php artisan migrate` instead of `php artisan module:migrate` command for migrate the migrations.

For the specified module.

```
php artisan module:publish-migration blog
```

For all modules.

```
php artisan module:publish-migration
```

Publish Module configuration files

```
php artisan module:publish-config <module-name>
```

- (optional) `module-name`: The name of the module to publish configuration. Leaving blank will publish all modules.
- (optional) `--force`: To force the publishing, overwriting already published files

Enable the specified module.


```
php artisan module:enable blog
```

Disable the specified module.

```
php artisan module:disable blog
```

Generate new middleware class.

```
php artisan module:make-middleware Auth
```

Update dependencies for the specified module.

```
php artisan module:update ModuleName
```

Update dependencies for all modules.

```
php artisan module:update
```

Show the list of modules.

```
php artisan module:list
```

<a name="facades"></a>
## Facades

Get all modules.

```php
Module::all();
```

Get all cached modules.

```php
Module::getCached()
```

Get ordered modules. The modules will be ordered by the `priority` key in `module.json` file.

```php
Module::getOrdered();
```

Get scanned modules.

```php
Module::scan();
```

Find a specific module.

```php
Module::find('name');
// OR
Module::get('name');
```

Find a module, if there is one, return the `Module` instance, otherwise throw `Nwidart\Modules\Exeptions\ModuleNotFoundException`.

```php
Module::findOrFail('module-name');
```

Get scanned paths.

```php
Module::getScanPaths();
```

Get all modules as a collection instance.

```php
Module::toCollection();
```

Get modules by the status. 1 for active and 0 for inactive.

```php
Module::getByStatus(1);
```

Check the specified module. If it exists, will return `true`, otherwise `false`.

```php
Module::has('blog');
```

Get all enabled modules.

```php
Module::enabled();
```

Get all disabled modules.

```php
Module::disabled();
```

Get count of all modules.

```php
Module::count();
```

Get module path.

```php
Module::getPath();
```

Register the modules.

```php
Module::register();
```

Boot all available modules.

```php
Module::boot();
```

Get all enabled modules as collection instance.

```php
Module::collections();
```

Get module path from the specified module.

```php
Module::getModulePath('name');
```

Get assets path from the specified module.

```php
Module::getAssetPath('name');
```

Get config value from this package.

```php
Module::config('composer.vendor');
```

Get used storage path.

```php
Module::getUsedStoragePath();
```

Get used module for cli session.

```php
Module::getUsedNow();
// OR
Module::getUsed();
```

Set used module for cli session.

```php
Module::setUsed('name');
```

Get modules's assets path.

```php
Module::getAssetsPath();
```

Get asset url from specific module.

```php
Module::asset('blog:img/logo.img');
```

Install the specified module by given module name.

```php
Module::install('nwidart/hello');
```

Update dependencies for the specified module.

```php
Module::update('hello');
```

<a name="entity"></a>
## Module Entity

Get an entity from a specific module.

```php
$module = Module::find('blog');
```

Get module name.

```
$module->getName();
```

Get module name in lowercase.

```
$module->getLowerName();
```

Get module name in studlycase.

```
$module->getStudlyName();
```

Get module path.

```
$module->getPath();
```

Get extra path.

```
$module->getExtraPath('Assets');
```

Disable the specified module.

```
$module->enable();
```

Enable the specified module.

```
$module->disable();
```

Delete the specified module.

```
$module->delete();
```

<a name="namespaces"></a>
## Custom Namespaces

When you create a new module it also registers new custom namespace for `Lang`, `View` and `Config`. For example, if you create a new module named blog, it will also register new namespace/hint blog for that module. Then, you can use that namespace for calling `Lang`, `View` or `Config`. Following are some examples of its usage:

Calling Lang:

```php
Lang::get('blog::group.name');
```

Calling View:

```php
View::make('blog::index')

View::make('blog::partials.sidebar')
```

Calling Config:

```php
Config::get('blog.name')
```

## Publishing Modules

Have you created a laravel modules? Yes, I've. Then, I want to publish my modules. Where do I publish it? That's the question. What's the answer ? The answer is [Packagist](http://packagist.org).

<a name="auto-scan-vendor-directory"></a>
### Auto Scan Vendor Directory

By default the `vendor` directory is not scanned automatically, you need to update the configuration file to allow that. Set `scan.enabled` value to `true`. For example :

```php
// file config/modules.php

return [
  //...
  'scan' => [
    'enabled' => true
  ]
  //...
]
```

You can verify the module has been installed using `module:list` command:

```
php artisan module:list
```

<a name="publishing-modules"></a>
## Publishing Modules

After creating a module and you are sure your module module will be used by other developers. You can push your module to [github](https://github.com) or [bitbucket](https://bitbucket.org) and after that you can submit your module to the packagist website.

You can follow this step to publish your module.

1. Create A Module.
2. Push the module to github.
3. Submit your module to the packagist website.
Submit to packagist is very easy, just give your github repository, click submit and you done.


## Credits

- [Nicolas Widart](https://github.com/nwidart)
- [gravitano](https://github.com/gravitano)
- [All Contributors](../../contributors)

## About Nicolas Widart

Nicolas Widart is a freelance web developer specialising on the laravel framework. View all my packages [on my website](https://nicolaswidart.com/projects).


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
