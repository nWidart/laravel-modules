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

`module:make` generates this structure. [Anatomy of a module](/laravel-modules/concepts/module-anatomy/) explains why each part is there. Module code is plain Laravel: controllers, Eloquent models, Blade views, and migrations.

## How the package finds and boots modules

The package scans the module folder for `module.json` files when the application boots. It registers the service providers of every enabled module in `priority` order. Composer autoloads module classes through `wikimedia/composer-merge-plugin`. See [How modules boot](/laravel-modules/concepts/how-modules-boot/) for the full sequence.

## Enabled and disabled

A module is enabled or disabled. Enabled modules register their providers, routes, views, and migrations. Disabled modules stay on disk without registering providers or routes.

The package stores status outside `module.json` in an activator (by default, `modules_statuses.json` at the application root). New modules are enabled unless you pass `--disabled`. See [Module status and activators](/laravel-modules/concepts/status-and-activators/).

## Commands

The package adds Artisan commands in five groups (see [Artisan commands](/laravel-modules/reference/commands/)):

- **Module management:** list, enable, disable, install, update, and delete modules (`module:list`, `module:enable`, `module:disable`).
- **Generators:** scaffold modules and the classes inside them (`module:make`, `module:make-controller`, `module:make-model`).
- **Database:** run migrations and seeders for one or all modules (`module:migrate`, `module:seed`).
- **Publish:** copy module configs, migrations, translations, assets, or Inertia entry files to the application (`module:publish-config`).
- **Utility:** setup, autoload migration, and PHPUnit coverage updates (`module:setup`, `module:update-phpunit-coverage`).

## When modules help

- The application has grown and several features, teams, or domains share one `app/` folder. A module gives each a folder to own.
- You want to turn a feature off without deleting it, for example per customer or per environment.
- You want a feature structured like a package so you can copy, move, or extract it to Composer later.

## When they do not

- The application is small. One `app/` folder is easier to navigate than several modules.
- You need strict isolation. A module is a folder convention that shares the container, database, config, and autoloader with the rest of the app. The package does not prevent one module from using another module's classes.
- You need separate deployments. Modules deploy together with the application. Build separate applications when you need independent deploys.

## What the package does not do

- It does not isolate modules or declare dependencies between them. `module.json` has no required-module field.
- It does not version modules or load them from a database. Modules are folders found on disk.
- It does not provide an admin interface, an end-user installer, or a theme system. It provides generators and a module registry.
- It does not change how Laravel works inside a module.

## Where to go next

- **Start here:** [Installation](/laravel-modules/start-here/installation/) sets up the package and autoloading. [Your first module](/laravel-modules/start-here/first-module/) builds a `Blog` module step by step.
- **Concepts:** [Anatomy of a module](/laravel-modules/concepts/module-anatomy/) and [How modules boot](/laravel-modules/concepts/how-modules-boot/) explain how the parts fit together.
- **Guides:** task-based steps, including [Create a module](/laravel-modules/guides/modules/create-a-module/), [Add routes to a module](/laravel-modules/guides/http/routes/), and [Deploy an application that uses modules](/laravel-modules/guides/production/deploy/).
- **Reference:** [Artisan commands](/laravel-modules/reference/commands/), [Configuration](/laravel-modules/reference/configuration/), [module.json](/laravel-modules/reference/module-json/), and [Module facade and repository](/laravel-modules/reference/api/repository/).
- **Upgrading:** [Version compatibility](/laravel-modules/upgrading/compatibility/) and [Upgrade from 12 to 13](/laravel-modules/upgrading/upgrade-to-13/).
- **Ecosystem:** [Community packages](/laravel-modules/ecosystem/community-packages/), [AI coding assistants](/laravel-modules/ecosystem/ai-assistants/), and [Contribute and get help](/laravel-modules/ecosystem/contributing-and-support/).
