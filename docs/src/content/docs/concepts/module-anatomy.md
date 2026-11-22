---
title: Anatomy of a module
description: What a module folder contains, why module.json and composer.json are separate, and how namespaces map to app/.
---

A module is a folder shaped like a small Laravel package. This page explains what each part holds and why. For creation steps, see [Create a module](/laravel-modules/guides/modules/create-a-module/). For every manifest field, see [module.json](/laravel-modules/reference/module-json/).

## What `module:make` generates

With default config, `php artisan module:make Blog` creates:

```text
Modules/Blog/
├── app/
│   ├── Http/Controllers/
│   └── Providers/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── assets/
│   └── views/
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── composer.json
├── module.json
├── package.json
└── vite.config.js
```

A folder is generated initially only when its `generate` flag is `true` in `modules.paths.generator`. Each stub file follows the flag of the generator key that owns it. See [Generators and stubs](/laravel-modules/concepts/generators-and-stubs/#what-generate-does).

Later `module:make-*` commands create folders such as `app/Models/` on demand. A module created with `--plain` includes the folders and `module.json`, with no stub files or `composer.json`, so its classes do not autoload. See [Autoloading](/laravel-modules/concepts/autoloading/#plain-modules).

## Two manifests, two readers

A module has two JSON files because two tools read them.

| File | Read by | Holds |
| --- | --- | --- |
| `module.json` | This package | `name`, `description` (read by `Module::getDescription()` and `ModulesEndpoint`), `priority`, `providers`, `aliases`, and `files`. `ModulesEndpoint` also reads optional `version`. `module:make` writes `alias` and `keywords` too, which the package does not read. |
| `composer.json` | Composer | `autoload` and `autoload-dev` maps, plus optional `require`, `require-dev`, and `scripts`. |

`module.json` tells the package how to boot the module. `composer.json` tells PHP how to find its classes.

Keeping them separate lets a module follow Composer's standard format, so you can extract it into a standalone Composer package later without rewriting autoload rules. `module.json` is the only file in the folder owned by this package. Everything else is ordinary Laravel code, with the main provider extending `ModuleServiceProvider`.

The two files overlap on provider lists. Laravel package discovery reads `extra.laravel.providers` in `composer.json` without checking module status, which would register providers even for disabled modules. This package registers providers from `module.json` after checking status, so the generated `composer.json` leaves `extra.laravel.providers` empty. See [module.json](/laravel-modules/reference/module-json/).

Module status (enabled or disabled) lives in neither file. See [Module status and activators](/laravel-modules/concepts/status-and-activators/).

## The `app/` folder and the namespace

The module's `composer.json` maps `Modules\Blog\` to `modules.paths.app_folder`, which defaults to `app/`. A class in `Modules/Blog/app/Http/Controllers/PostController.php` therefore has the namespace `Modules\Blog\Http\Controllers`. The `app/` segment is the namespace root and does not appear in the class name.

This mirrors a standard Laravel application, where `App\` maps to `app/`.

Three folders outside `app/` get their own autoload entries:

| Namespace | Folder |
| --- | --- |
| `Modules\Blog\Database\Factories\` | `database/factories/` |
| `Modules\Blog\Database\Seeders\` | `database/seeders/` |
| `Modules\Blog\Tests\` | `tests/` (in `autoload-dev`) |

Generator paths and `app_folder` must agree so generated classes autoload. `module:make` exits with an error if a class generator path sits outside `app_folder`. See [Change the folder layout of modules](/laravel-modules/guides/modules/change-the-folder-layout/) and [Generators and stubs](/laravel-modules/concepts/generators-and-stubs/).

## Providers are the main entry point

A module has no separate bootstrap file. The package reads `providers`, `aliases`, and `files` from `module.json` during boot and hands the providers to Laravel. See [How modules boot](/laravel-modules/concepts/how-modules-boot/).

The main provider extends [`ModuleServiceProvider`](/laravel-modules/reference/api/module-service-provider/). It registers config, views, translations, migrations, commands (`$commands`), schedules (`configureSchedules()`), and Actuator health indicators (`$healthIndicators`). It also registers additional providers listed in `$providers` — by default, a route provider and an event provider.

Because providers drive module behaviour, a module can do anything a Laravel service provider can do. The package adds no custom module lifecycle on top. If a module needs non-standard setup, change its provider rather than the package.

## A module is a convention, not a boundary

A module uses the same layout as a Laravel package: `composer.json`, providers, `routes/`, `config/`, `resources/`, `database/`, and `tests/`. This makes it straightforward to move a module into its own repository later. See [Publish a module as a Composer package](/laravel-modules/guides/distribute/publish-a-module-as-a-package/).

The trade-off is that modules share the container, database, config, and autoloader with the host application. See the [introduction](/laravel-modules/start-here/introduction/) for what the package does not enforce.
