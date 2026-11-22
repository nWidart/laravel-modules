---
title: Configuration
description: Every key of config/modules.php with its default value and its effect.
---

The package ships `config/config.php` and merges it under the `modules` key. The `config` tag of `vendor:publish` copies it to `config/modules.php` (see [vendor:publish tags](/laravel-modules/reference/commands/publish/#vendorpublish-tags)). A value is read with `config('modules.<key>')`, for example `config('modules.paths.modules')`.

Keys marked **deprecated** have no effect in version 13 and are removed in version 14.

## Top-level keys

| Key | Default | Description |
| --- | --- | --- |
| `namespace` | `'Modules'` | Root namespace of the modules. Generators and `ModuleServiceProvider` prefix every class namespace with it, and `module:make` writes it into the `autoload.psr-4` entry of the module `composer.json`. A module class autoloads only when the `composer.json` of the application merges that file: its `extra.merge-plugin.include` glob must match the modules folder (see `paths.modules`). |
| `vapor_maintenance_mode` | `env('VAPOR_MAINTENANCE_MODE', false)` | When `true`, the providers manifest is placed next to Laravel's config cache file instead of its services cache file. The providers manifest is the cache file `modules.php` (by default `bootstrap/cache/modules.php`) that lists the providers of the enabled modules. |
| `activator` | `'file'` | Name of the active activator: a key of `activators`. An unknown name throws `InvalidActivatorClass`. |
| `commands` | `ConsoleServiceProvider::defaultCommands()` merged with an empty list | A list of Artisan command class names. `ConsoleServiceProvider` registers every class of the list. |

The `commands` value is an array of class names. The default `config/config.php` builds it from the default commands and a `merge()` call that takes the extra class names:

```php
'commands' => ConsoleServiceProvider::defaultCommands()
    ->merge([
        \Modules\Blog\Console\PruneCommand::class,
    ])->toArray(),
```

## stubs

| Key | Default | Description |
| --- | --- | --- |
| `stubs.enabled` | `false` | When `true`, the generators read stubs from `stubs.path` first. When `false`, they read the package stubs. |
| `stubs.path` | `base_path('vendor/nwidart/laravel-modules/src/Commands/stubs')` | Directory of the stubs used when `stubs.enabled` is `true`. A stub missing from this directory falls back to the package stub. |
| `stubs.files` | see below | The files `module:make` writes into a new module, as `stub key => path inside the module`. |
| `stubs.replacements` | see below | The replacement keys available to each stub of `stubs.files`. See [Stubs and placeholders](/laravel-modules/reference/stubs/). |
| `stubs.gitkeep` | `true` | Write an empty `.gitkeep` file into each folder `module:make` creates. |

### Default `stubs.files`

| Stub key | Path inside the module |
| --- | --- |
| `routes/web` | `routes/web.php` |
| `routes/api` | `routes/api.php` |
| `views/index` | `resources/views/index.blade.php` |
| `views/master` | `resources/views/components/layouts/master.blade.php` |
| `scaffold/config` | `config/config.php` |
| `composer` | `composer.json` |
| `assets/js/app` | `resources/assets/js/app.js` |
| `assets/sass/app` | `resources/assets/sass/app.scss` |
| `vite` | `vite.config.js` |
| `package` | `package.json` |

### Default `stubs.replacements`

| Stub key | Replacement keys |
| --- | --- |
| `routes/web`, `routes/api` | `LOWER_NAME`, `STUDLY_NAME`, `PLURAL_LOWER_NAME`, `KEBAB_NAME`, `MODULE_NAMESPACE`, `CONTROLLER_NAMESPACE` |
| `vite` | `LOWER_NAME`, `STUDLY_NAME`, `KEBAB_NAME` |
| `json` | `LOWER_NAME`, `STUDLY_NAME`, `KEBAB_NAME`, `MODULE_NAMESPACE`, `PROVIDER_NAMESPACE` |
| `views/index` | `LOWER_NAME` |
| `views/master` | `LOWER_NAME`, `STUDLY_NAME`, `KEBAB_NAME` |
| `scaffold/config` | `STUDLY_NAME` |
| `composer` | `LOWER_NAME`, `STUDLY_NAME`, `VENDOR`, `AUTHOR_NAME`, `AUTHOR_EMAIL`, `MODULE_NAMESPACE`, `PROVIDER_NAMESPACE`, `APP_FOLDER_NAME` |

The `json` entry has no counterpart in `stubs.files`: `module:make` always writes `module.json`, from the `json` stub, so the file is not a `stubs.files` entry. `module:make` adds `PROVIDER_NAMESPACE` to the `json` and `composer` lists when they lack it. It also adds `APP_FOLDER_NAME` to `composer` and `PLURAL_LOWER_NAME` to `routes/web` and `routes/api`, unless the list maps that name to a class.

## paths

| Key | Default | Description |
| --- | --- | --- |
| `paths.modules` | `base_path('Modules')` | Folder that holds the modules. `module:make` writes here. It is always one of the scanned folders. The `extra.merge-plugin.include` glob in the `composer.json` of the application must match it (default `Modules/*/composer.json`), or the module classes do not autoload. See [Installation](/laravel-modules/start-here/installation/), step 2. |
| `paths.assets` | `public_path('modules')` | Folder where `module:publish` copies the module assets, one sub-folder per module. `module:setup` creates it. |
| `paths.migration` | `base_path('database/migrations')` | Folder where `module:publish-migration` copies the module migrations. |
| `paths.app_folder` | `'app/'` | Folder inside each module that holds its classes. The module `composer.json` maps the module namespace to it. `module:make` stops when a class generator path is not inside it. A class generator path is the `path` of a `paths.generator` entry that holds PHP classes: the entries from `actions` to `request` in the table below. An empty string maps the namespace to the module root; the class generator paths must then drop the `app/` prefix and use StudlyCase folders. |

### paths.generator

`paths.generator` has one entry per kind of folder or file. An entry is an array:

```php
'actions' => ['path' => 'app/Actions', 'generate' => false],
```

| Field | Description |
| --- | --- |
| `path` | Folder inside the module, relative to the module root. |
| `generate` | `true`: `module:make` creates the folder (and the files it owns, see below). `false` or missing: `module:make` does not create it. The generator command that uses the key still works. |
| `namespace` | Optional. Namespace of the generated classes, relative to the module namespace. Default: `path` without `paths.app_folder`, converted to a namespace. |

A plain string value is read as `path`, and `generate` is then `true` when the string is not empty. A `false` value disables the key.

Setting `generate` to `false` also skips the `stubs.files` that a key owns:

| Key | Skipped stubs |
| --- | --- |
| `routes` | `routes/web`, `routes/api` |
| `views` | `views/index`, `views/master` |
| `config` | `scaffold/config` |
| `assets` | `assets/js/app`, `assets/sass/app`, `vite`, `package` |

#### Default entries

| Key | Default path | Generated by `module:make` |
| --- | --- | --- |
| `actions` | `app/Actions` | no |
| `casts` | `app/Casts` | no |
| `channels` | `app/Broadcasting` | no |
| `class` | `app/Classes` | no |
| `command` | `app/Console` | no |
| `command_replacements` | `app/Console/Replacements` | no |
| `component-class` | `app/View/Components` | no |
| `emails` | `app/Emails` | no |
| `event` | `app/Events` | no |
| `enums` | `app/Enums` | no |
| `exceptions` | `app/Exceptions` | no |
| `jobs` | `app/Jobs` | no |
| `job-middleware` | `app/Jobs/Middleware` | no |
| `helpers` | `app/Helpers` | no |
| `interfaces` | `app/Interfaces` | no |
| `listener` | `app/Listeners` | no |
| `model` | `app/Models` | no |
| `notifications` | `app/Notifications` | no |
| `observer` | `app/Observers` | no |
| `policies` | `app/Policies` | no |
| `provider` | `app/Providers` | yes |
| `repository` | `app/Repositories` | no |
| `resource` | `app/Transformers` | no |
| `route-provider` | `app/Providers` | yes |
| `rules` | `app/Rules` | no |
| `services` | `app/Services` | no |
| `scopes` | `app/Models/Scopes` | no |
| `traits` | `app/Traits` | no |
| `controller` | `app/Http/Controllers` | yes |
| `filter` | `app/Http/Middleware` | no |
| `request` | `app/Http/Requests` | no |
| `config` | `config` | yes |
| `factory` | `database/factories` | yes |
| `migration` | `database/migrations` | yes |
| `seeder` | `database/seeders` | yes |
| `lang` | `lang` | no |
| `assets` | `resources/assets` | yes |
| `component-view` | `resources/views/components` | no |
| `views` | `resources/views` | yes |
| `inertia` | `resources/js/Pages` | no |
| `inertia-components` | `resources/js/Components` | no |
| `routes` | `routes` | yes |
| `test-feature` | `tests/Feature` | yes |
| `test-unit` | `tests/Unit` | yes |

The key `event-provider` is read by the code but is not in `config/config.php`. `module:make-event-provider` writes to the `provider` path. `module:make` generates the event provider when an `event-provider` entry has `generate` set to `true`, or, when the entry has no `path`, when `provider` is generated.

## auto-discover

| Key | Default | Description |
| --- | --- | --- |
| `auto-discover.migrations` | `true` | Register the migration path of every enabled module with Laravel's migrator, so `php artisan migrate` runs module migrations. See [Module migrations and `php artisan migrate`](/laravel-modules/reference/commands/database/#module-migrations-and-php-artisan-migrate). |
| `auto-discover.translations` | `false` | Register the `lang` path of every enabled module with the translator, under the lower-case module name as namespace, and as a JSON path. Each module provider that extends `ModuleServiceProvider` registers the same namespace in `registerTranslations()`. See [Translations](/laravel-modules/reference/api/module-service-provider/#translations). |

## scan

| Key | Default | Description |
| --- | --- | --- |
| `scan.enabled` | `false` | Also scan the folders in `scan.paths` for `module.json` files. |
| `scan.paths` | `[base_path('vendor/*/*')]` | Glob patterns of the extra folders to scan. A `/*` is appended when missing. |

## composer

Values the `composer` stub receives when `module:make` generates a module `composer.json`.

| Key | Default | Description |
| --- | --- | --- |
| `composer.vendor` | `env('MODULE_VENDOR', 'nwidart')` | Vendor part of the package name (`$VENDOR$`). Overridden by `module:make --author-vendor`. |
| `composer.author.name` | `env('MODULE_AUTHOR_NAME', 'Nicolas Widart')` | Author name (`$AUTHOR_NAME$`). Overridden by `--author-name`. |
| `composer.author.email` | `env('MODULE_AUTHOR_EMAIL', 'n.widart@gmail.com')` | Author email (`$AUTHOR_EMAIL$`). Overridden by `--author-email`. |
| `composer.composer-output` | `false` | When `false`, `module:update` runs Composer with `--quiet`. |

## register

**Deprecated.** Only the deprecated `Module::boot()` and `Module::register()` read these keys, and the package does not call them.

| Key | Default | Description |
| --- | --- | --- |
| `register.translations` | `true` | Deprecated. No effect. |
| `register.files` | `'register'` | Deprecated. No effect. Module `files` are loaded when the package registers the modules. |

## activators

| Key | Default | Description |
| --- | --- | --- |
| `activators` | `['file' => [...]]` | Activator definitions, keyed by name. The `activator` key selects one. The container reads its class from `activators.<key>.class`, which must implement `Nwidart\Modules\Contracts\ActivatorInterface`, and constructs it with `new $class($app)`: the constructor receives the container. |
| `activators.file.class` | `Nwidart\Modules\Activators\FileActivator::class` | Default activator. |
| `activators.file.statuses-file` | `base_path('modules_statuses.json')` | JSON file where `FileActivator` stores the module statuses. A module missing from this file counts as disabled. `module:update-phpunit-coverage` reads it too. |

## inertia

| Key | Default | Description |
| --- | --- | --- |
| `inertia.frontend` | `'vue'` | Framework used by `module:make-inertia-page`, `module:make-inertia-component` and `module:publish-inertia` when no `--vue`, `--react` or `--svelte` option is given. Values: `vue`, `react`, `svelte`. |
