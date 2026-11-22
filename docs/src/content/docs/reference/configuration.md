---
title: Configuration
description: Every key of config/modules.php with its default value and its effect.
---

The package ships `config/config.php` and merges it under the `modules` key. The `config` tag of `vendor:publish` copies it to `config/modules.php` (see [vendor:publish tags](/laravel-modules/reference/commands/publish/#vendorpublish-tags)). A value is read with `config('modules.<key>')`, for example `config('modules.paths.modules')`.

Keys marked **deprecated** have no effect in version 13 and are removed in version 14.

## Top-level keys

| Key | Default | Description |
| --- | --- | --- |
| `namespace` | `'Modules'` | Root namespace of the modules. Generators and `ModuleServiceProvider` prefix class namespaces with it. `module:make` writes it to `autoload.psr-4` in the module `composer.json`. The application `composer.json` must merge that file via `extra.merge-plugin.include` (matching `paths.modules`). |
| `vapor_maintenance_mode` | `env('VAPOR_MAINTENANCE_MODE', false)` | When `true`, places the `modules.php` providers manifest next to Laravel's config cache instead of its services cache (`bootstrap/cache/modules.php` by default). |
| `activator` | `'file'` | Name of the active activator: a key of `activators`. An unknown name throws `InvalidActivatorClass`. |
| `commands` | `ConsoleServiceProvider::defaultCommands()` merged with an empty list | List of Artisan command class names. `ConsoleServiceProvider` registers every class in the list. |

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

`module:make` always writes `module.json` from the `json` stub, so `json` is not in `stubs.files`. `module:make` adds missing default keys automatically unless mapped to a custom class:

- `PROVIDER_NAMESPACE` to `json` and `composer`.
- `APP_FOLDER_NAME` to `composer`.
- `PLURAL_LOWER_NAME` to `routes/web` and `routes/api`.

## paths

| Key | Default | Description |
| --- | --- | --- |
| `paths.modules` | `base_path('Modules')` | Folder that holds modules. `module:make` writes here, and the repository always scans it. Match it in `extra.merge-plugin.include` (default `Modules/*/composer.json`) so classes autoload. See [Installation](/laravel-modules/start-here/installation/). |
| `paths.assets` | `public_path('modules')` | Folder where `module:publish` copies module assets, one sub-folder per module. `module:setup` creates it. |
| `paths.migration` | `base_path('database/migrations')` | Folder where `module:publish-migration` copies module migrations. |
| `paths.app_folder` | `'app/'` | Folder inside each module that holds its classes. The module `composer.json` maps the module namespace to it. Every class generator path (`actions` through `request` below) must sit inside it, or `module:make` stops. Set `''` to map to the module root, then drop the `app/` prefix and use StudlyCase folders. |

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

The `health` key is not in `config/config.php`, but the code reads it. `module:make-health-indicator` defaults to `paths.app_folder` plus `Health` (`app/Health`). Set `modules.paths.generator.health` with `path` and optional `namespace` to override it. A configured `health` path must stay inside `paths.app_folder`, or `module:make` stops.

The `event-provider` key is not in `config/config.php`, but the code reads it. `module:make-event-provider` writes to the `provider` path. `module:make` generates `EventServiceProvider` when `event-provider.generate` is `true`, or when `event-provider` has no `path` and `provider.generate` is `true`.

## auto-discover

| Key | Default | Description |
| --- | --- | --- |
| `auto-discover.migrations` | `true` | Registers each enabled module's migration path with Laravel's migrator for `php artisan migrate`. See [Module migrations and `php artisan migrate`](/laravel-modules/reference/commands/database/#module-migrations-and-php-artisan-migrate). |
| `auto-discover.translations` | `false` | Registers each enabled module's `lang` path with the translator under the lower-case module namespace and as a JSON path. `ModuleServiceProvider::registerTranslations()` registers the same namespace. See [Translations](/laravel-modules/reference/api/module-service-provider/#translations). |

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
| `activators` | `['file' => [...]]` | Activator definitions, keyed by name and selected via `activator`. Each entry defines a `class` implementing `Nwidart\Modules\Contracts\ActivatorInterface`. The container instantiates it with `new $class($app)`. |
| `activators.file.class` | `Nwidart\Modules\Activators\FileActivator::class` | Default activator. |
| `activators.file.statuses-file` | `base_path('modules_statuses.json')` | JSON file where `FileActivator` stores module statuses. Missing modules count as disabled. `module:update-phpunit-coverage` reads it too. |

## inertia

| Key | Default | Description |
| --- | --- | --- |
| `inertia.frontend` | `'vue'` | Default framework for Inertia commands when no `--vue`, `--react`, or `--svelte` flag is passed. Stubs target Inertia v3. Values: `vue`, `react`, `svelte`. |

## actuator

| Key | Default | Description |
| --- | --- | --- |
| `actuator.enabled` | `true` | Registers the `modules` and module `$healthIndicators` health checks when [`nwidart/laravel-actuator`](https://github.com/nWidart/laravel-actuator) is installed. Set `false` to disable both; a missing key counts as `true`. Does not affect the `modules` actuator endpoint. See [Actuator integration](/laravel-modules/reference/api/actuator/), [Report module health with the actuator](/laravel-modules/guides/production/health-checks/), and [Expose the modules on the actuator](/laravel-modules/guides/production/actuator-modules-endpoint/). |
