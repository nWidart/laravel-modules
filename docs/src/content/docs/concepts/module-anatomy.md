---
title: Anatomy of a module
description: What a module folder contains, why module.json and composer.json are separate files, and how the namespace maps to the app folder.
---

A module is a folder that looks like a small Laravel package. This page explains what is in that folder and why the package chose this shape. For the steps to create one, see [Create a module](/laravel-modules/guides/modules/create-a-module/). For every field of the manifest, see [module.json](/laravel-modules/reference/module-json/).

## What `module:make` generates

`php artisan module:make Blog` creates, with the default config:

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

Only the folders and files whose `generate` flag is `true` in `modules.paths.generator` exist at first. Later `module:make-*` commands add folders such as `app/Models/` when you need them. A module created with `--plain` has the folders and `module.json`, and none of the stub files, so it has no `composer.json`.

## Two manifests, two readers

A module has two JSON files because two different tools read them.

| File | Read by | Holds |
| --- | --- | --- |
| `module.json` | This package | The module `name` and `alias`, the `priority`, the `providers` to register, and the `files` to include. |
| `composer.json` | Composer | The `autoload` and `autoload-dev` maps, and optionally `require`, `require-dev` and `scripts`. |

`module.json` answers "what does the package do with this module at boot". `composer.json` answers "how does PHP find the classes". Keeping them apart lets a module follow Composer's format exactly, so it can later become a real Composer package without a rewrite. The package does not invent its own autoload format.

The two files do overlap in one place: both can name providers. The package registers the providers in `module.json`. The `extra.laravel.providers` list of the generated `composer.json` is empty.

The status of a module (enabled or disabled) is in neither file. See [Module status and activators](/laravel-modules/concepts/status-and-activators/).

## The `app/` folder and the namespace

The module `composer.json` maps the namespace `Modules\Blog\` to the folder `modules.paths.app_folder`, which is `app/` by default. A class in `Modules/Blog/app/Http/Controllers/PostController.php` is therefore `Modules\Blog\Http\Controllers\PostController`. The `app/` segment is the namespace root, and it does not appear in the class name.

This mirrors a Laravel application, where `App\` maps to `app/`. A developer who knows where a controller goes in an application knows where it goes in a module.

Three other namespaces have their own entries, because their folders sit outside `app/`:

| Namespace | Folder |
| --- | --- |
| `Modules\Blog\Database\Factories\` | `database/factories/` |
| `Modules\Blog\Database\Seeders\` | `database/seeders/` |
| `Modules\Blog\Tests\` | `tests/` (in `autoload-dev`) |

The generator paths and the app folder must agree, or the classes that the generators write cannot autoload. For that reason `module:make` stops with an error when a class generator path sits outside the app folder. To change the layout, see [Change the folder layout](/laravel-modules/guides/modules/change-the-folder-layout/). How the namespace is derived from a path is in [Generators and stubs](/laravel-modules/concepts/generators-and-stubs/).

## Providers are the entry point

A module has no bootstrap file and no registry entry beyond `module.json`. The package reads `providers` from `module.json` and hands the classes to Laravel. From that point the module is ordinary Laravel code.

The main provider extends [`ModuleServiceProvider`](/laravel-modules/reference/api/module-service-provider/). It registers the config, views, translations and migrations of the module from the conventional folders, and it lists further providers in `$providers`. By default those are a route provider and an event provider.

This is a design choice with a consequence. Because the provider is the only entry point, anything Laravel lets a provider do, a module can do: bind services, register commands, define schedules. The package adds no module-specific lifecycle on top. It also means a module that needs something unusual changes its provider, not the package.

## Why it looks like a package

A module has its own `composer.json`, its own providers, its own `routes/`, `config/`, `resources/`, `database/` and `tests/`. These are the folders of a Laravel package. The reasons:

- **Familiar structure.** Nothing is specific to this package except two files.
- **Extractable.** A module can move to its own repository and be required through Composer. See [Publish a module as a package](/laravel-modules/guides/distribute/publish-a-module-as-a-package/).
- **Local ownership.** A team that owns a feature owns one folder.

The trade-off is that a module is a convention, not a boundary. It shares the container, database, config and autoloader with the application. The [introduction](/laravel-modules/start-here/introduction/) lists what the package does not enforce.
