---
title: Introduction
description: What Laravel Modules is, what a module is, and when splitting an application into modules helps.
---

Laravel Modules splits one Laravel application into modules. Each module lives in its own folder and holds its own routes, controllers, models, views, migrations, config and translations. The application stays one deployable Laravel app.

## What a module is

A module is a small Laravel package inside your application. By default it lives in `Modules/<Name>/` at the application root. A module has:

- a `module.json` file that names the module and lists its service providers and files;
- a `composer.json` file that maps the module namespace (`Modules\<Name>\`) to the folder of its classes;
- a main service provider, usually with a route provider, that registers its routes, views, config, translations and migrations;
- its own `routes/`, `resources/views/`, `database/migrations/`, `config/` and `tests/` folders.

The `module:make` command generates this structure. You then work in the module as you work in `app/`. The module code is plain Laravel: controllers, Eloquent models, Blade views, migrations.

## How the package finds and boots modules

The package scans the module folder for `module.json` files when the application boots. It registers the service providers of every enabled module, in the order of the `priority` field. Module classes autoload through the module `composer.json`, which Composer merges into the application autoload with `wikimedia/composer-merge-plugin`.

## Enabled and disabled

A module is enabled or disabled. The package registers the providers of an enabled module, so its routes, views and migrations are active. It does not register a disabled module: its routes disappear and its migrations are not discovered. The files stay on disk.

The package stores the status outside `module.json`, in an activator. The default activator writes a `modules_statuses.json` file at the application root. A new module is enabled, unless you create it with `--disabled`.

## Commands

The package adds Artisan commands in three groups: commands that create modules and the classes inside them (`module:make`, `module:make-controller`, `module:make-model`), commands that manage modules (`module:list`, `module:enable`, `module:disable`), and commands that run Laravel's database commands for one module or for all of them (`module:migrate`, `module:seed`).

## When modules help

- The application has grown and several features, teams or domains share one `app/` folder. A module gives each of them a folder to own.
- You want to turn a feature off without deleting it, for example for one customer or one environment.
- You want a feature to be a unit you can copy, move or extract to a Composer package later. A module has the structure of a package.

## When they do not

- The application is small. One `app/` folder is easier to read than several modules.
- You need isolation. A module is a folder convention. It shares the container, the database, the config and the autoloader with the rest of the application, and the package does not stop a module from using another module's classes.
- You need separate deployments. Modules deploy together with the application. For separate deployments, build separate applications.

## What the package does not do

- It does not isolate modules or declare dependencies between them. `module.json` has no field for a required module.
- It does not version modules or load them from a database. Modules are folders that the scan finds.
- It does not provide an admin interface, an installer for end users or a theme system. It provides generators and a registry of modules.
- It does not change how Laravel works inside a module.

## Where to go next

- [Installation](/laravel-modules/start-here/installation/): install the package and set up autoloading.
- [Your first module](/laravel-modules/start-here/first-module/): build a small `Blog` module step by step.
- [Artisan commands](/laravel-modules/reference/commands/): every command, argument and option.
- [Configuration](/laravel-modules/reference/configuration/): every key of `config/modules.php`.
- [module.json](/laravel-modules/reference/module-json/): the fields of the module manifest.
- [Module facade and repository](/laravel-modules/reference/api/repository/): find and manage modules from PHP.
