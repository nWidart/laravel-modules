---
title: How modules boot
description: How LaravelModulesServiceProvider boots modules, where module providers run, what is cached, and what disabled modules cost.
---

This page follows one request through the package, from `LaravelModulesServiceProvider` to a module route. It explains what the package reads, in which order, and what it caches. For module load order, see [Control the load order of modules](/laravel-modules/guides/modules/control-the-load-order/).

## The sequence

```text
LaravelModulesServiceProvider::register()
  1. merge package config; bind repository, activator and module manifest;
     queue stub base path; register console, migration, translation,
     event-discovery and actuator-endpoint hooks
  registerModules()
    2. scan module.json files, keep enabled modules, sort by priority
    3. register class aliases
    4. load providers through ProviderRepository (bootstrap/cache/modules.php):
       register() of each module provider merges module config, then registers $providers
    5. include files
Laravel boots all providers
  6. LaravelModulesServiceProvider::boot():
     publish paths and AboutCommand (console only), lazy @module directive,
     modules health indicator
  7. ModuleServiceProvider::boot() of each module:
     commands, schedules, translations, views (lazy component namespaces),
     health indicators, migrations; route provider maps routes
```

### 1. Bindings and hooks

Laravel discovers `LaravelModulesServiceProvider` automatically. At the start of `register()`, it merges `config/config.php` under `modules`. Doing this first lets the console provider and auto-discovery hooks read merged config.

Next, it binds three singletons:

- the module repository (`RepositoryInterface`, alias `modules`);
- the activator (`ActivatorInterface`), chosen from `modules.activator` and `modules.activators`;
- the `ModuleManifest`.

These bindings are lazy, so nothing scans on disk yet.

The provider also queues a `booted()` callback to set the stub base path once every provider has registered. This lets it see config set by other providers. See [Generators and stubs](/laravel-modules/concepts/generators-and-stubs/#how-published-stubs-take-over).

Finally, it registers `ConsoleServiceProvider` and hooks migration and translation auto-discovery. It also enables module listener name guessing for Laravel event discovery and registers the `modules` Actuator endpoint when `nwidart/laravel-actuator` is installed.

### 2. Scan, filter and sort

`registerModules()` asks `ModuleManifest` for the data of enabled modules. The manifest:

1. Finds every `module.json` in the scan paths. By default, that is `modules.paths.modules`, plus `modules.scan.paths` when `modules.scan.enabled` is `true`. The package appends `/*` to any path that does not end with it and reads `<path>/module.json`. The default `vendor/*/*` entry therefore finds `vendor/acme/blog/module.json`. See [Load modules from extra locations](/laravel-modules/guides/modules/scan-extra-locations/).
2. Keeps modules that the activator marks enabled.
3. Sorts them in ascending order by `priority` in `module.json` (a missing priority counts as `0`). Lower numbers register first. Equal priorities keep scan order: configured scan paths first, then alphabetical folder order within each path.

The result is a priority-ordered list of provider classes, aliases, and files. A module found only by scanning has no status entry and stays disabled until you enable it. Its classes must autoload before you enable it; an enabled module with an unloadable provider stops every `artisan` command, unless you set [`skip_broken_modules`](#skip-broken-modules).

### 3. Aliases

Before providers load, the manifest registers each enabled module's `aliases` with Laravel's alias loader. When two modules declare the same alias, the one with the higher priority value (registered later) wins.

### 4. Providers and the providers manifest

The package passes the provider list to Laravel's `ProviderRepository`, the same class that manages `bootstrap/cache/services.php`. It writes its manifest next to it at `bootstrap/cache/modules.php` and rebuilds it whenever the provider list changes. Deferred providers register only when their services are resolved; eager providers register immediately.

The file is always named `modules.php` inside Laravel's cache folder. On Laravel Vapor, where `bootstrap/cache` is read-only, `modules.vapor_maintenance_mode` moves it next to the config cache. This file caches the provider list only, not the module scan. See [What is cached](#what-is-cached).

No command deletes `bootstrap/cache/modules.php`, and `php artisan optimize:clear` leaves it in place. Running `module:disable` does not delete it either. The next request detects the changed provider list and rewrites the file.

`ProviderRepository` calls `register()` on each eager provider while the package provider is still registering. Each module's `ModuleServiceProvider::register()` merges the module config first, then registers every class in `$providers` (by default, its route and event providers). Sub-providers can read the module config in their `register()`.

### 5. Files

After all enabled module providers register, the manifest includes each module's `files` using `include_once`. Helper files listed in `files` load once per request, after module provider registration finishes.

### 6. Boot

Laravel calls `boot()` on all providers once registration completes.

`LaravelModulesServiceProvider::boot()` runs three tasks:

- **Console only:** registers publish paths (`config`, `stubs`, `vite`) and adds the package version to `php artisan about`.
- **Blade directive:** registers the `@module` directive lazily via `callAfterResolving('blade.compiler')`, avoiding Blade compiler instantiation on requests that do not compile templates.
- **Health indicator:** calls `HealthIndicators::register()` to register the `modules` indicator with `nwidart/laravel-actuator`.

Each module's `ModuleServiceProvider::boot()` then registers:

- commands (`$commands`) and schedules (`configureSchedules()`, deferred until `Schedule` resolves);
- translations (and config only if a subclass skipped `parent::register()`);
- views, plus Blade component namespaces registered lazily via `callAfterResolving('blade.compiler')`;
- module health indicators (`registerHealthIndicators()`);
- module migrations (`loadMigrationsFrom()`).

The module's route provider extends Laravel's `RouteServiceProvider` and maps its route files. See [Module service provider](/laravel-modules/reference/api/module-service-provider/) for each step.

For the enabled/disabled filter in step 2, see [Module status and activators](/laravel-modules/concepts/status-and-activators/).

## Skip broken modules

By default, a broken module stops the application while it boots, so every request and `artisan` command fails:

- An invalid `module.json` throws `Nwidart\Modules\Exceptions\InvalidJsonException`.
- A provider class that does not exist throws `Class "..." not found`.

Set `skip_broken_modules` to `true` in `config/modules.php` to boot without the broken module:

```php title="config/modules.php"
'skip_broken_modules' => true,
```

An app with a published `config/modules.php` must add the key itself, or re-publish the config. The default is `false`.

With the flag on:

- **Invalid `module.json`:** the module is recorded under its folder name (or its directory relative to the base path, such as `extra-modules/Blog`, when a listed module already has that name), with a reason such as `Error processing file: Modules/Shop/module.json. Error: Syntax error` (path relative to the base path).
- **Missing provider class:** the package checks the providers of enabled modules. A provider that is an alias mapped to an existing class by the module's own `aliases` counts as present. If one class does not exist, it skips the whole module: its providers, aliases and `files` do not load. The reason is `Provider class [X] not found.` Disabled modules are not checked.
- **Log:** each skipped module writes one error entry per boot: `Module [Shop] was skipped because it is broken: <reason>`.
- **Reports:** `php artisan module:list` shows `[Broken]` with the reason, and the `modules` health indicator reports `DOWN`. See [`module:list`](/laravel-modules/reference/commands/module-management/#modulelist) and [Report module health with the actuator](/laravel-modules/guides/production/health-checks/).
- **PHP API:** `Nwidart\Modules\FileRepository::getBrokenModules()` returns module name => reason. See [Repository](/laravel-modules/reference/api/repository/#find-and-list).

:::caution
A skipped module silently loses its routes and services. Unless someone reads the logs or the health endpoint, the app runs on without it. Monitor the `modules` health indicator or the error log before you turn the flag on in production.
:::

Limits:

- A module skipped for a missing provider still counts as enabled in the repository (`Module::isEnabled()`, `allEnabled()`). `module:migrate` and seeders still run for it.
- A module with an invalid `module.json` has no readable name, so `module:enable` and `module:disable` cannot find it (`module:disable Shop` says it does not exist). Fix the JSON first.
- A route cache built with `php artisan route:cache` or `optimize` while the module was healthy still holds its routes after the module is skipped, and they run without the module's bindings. Run `php artisan route:clear`, or `route:cache` again, after a module breaks or is fixed.
- Skipping a module changes the provider list, so Laravel rewrites `bootstrap/cache/modules.php`, as when you enable or disable a module. `bootstrap/cache` must be writable.
- `module:make` refuses, without `--force`, a destination folder that holds a `module.json` the repository could not read, and prints `Module [X] already exists!`. This applies with or without the flag.
- A provider class that exists but throws in `register()` or `boot()` still stops the app.
- The broken list lasts for the process. Restart long-lived processes (Octane, queue workers) after you fix a module.
- The package autoloads every provider class of enabled modules at boot. The cost is small.

## Where module providers run among your own

The package loads module providers inside its own `register()`. Laravel registers discovered package providers before application providers in `bootstrap/providers.php`. Module providers therefore run ahead of your application providers:

- **Registration:** a module's `register()` runs before your `App\Providers` classes. When both bind the same abstract, your application binding registers later and wins.
- **Boot:** a module's `boot()` runs before your application providers' `boot()`.
- **Config merging:** a module merges its config files in `register()`, before registering `$providers`. The config is available in its sub-providers' `register()`, in later providers' `register()`, and in every provider's `boot()`. Existing values under the module key — published `config/` files or values set earlier — win via `mergeApplicationConfig()`. Associative arrays merge recursively; scalars, lists, and `[]` replace module values. Values set later in `register()` or `boot()` also win. See [Config files](/laravel-modules/reference/api/module-service-provider/#config-files).
- **Earlier packages:** a package Laravel discovers before this one cannot read module config in `register()`. Read it in `boot()` instead.

Other packages that Laravel discovers before this one register first. Laravel controls discovery order among packages.

The older `Module::boot()` and `Module::register()` methods handled this work themselves. The package no longer calls them; both are [deprecated](/laravel-modules/upgrading/deprecations/).

## What is cached

The package maintains two separate caches:

| Cache | Where | Lifetime | Holds |
| --- | --- | --- | --- |
| Providers manifest | `bootstrap/cache/modules.php` | On disk until the provider list changes | Enabled module providers stored by Laravel's `ProviderRepository`. |
| Scan | Memory, in repository and manifest instances | One PHP process | Decoded `module.json` files and the `Module` objects built from them. |

The package writes no other cache file and has no `module:cache` command. Writing the scan to disk saved about 2 ms at 200 modules while adding stale-cache risk. Earlier versions skipped the in-memory scan cache in tests, so each `module_path()` call re-read every `module.json`. Caching the scan in 13.3.0 cut a 200-module test from 3.3 s to 249 ms.

Laravel's route cache is separate from the package. With cached routes, a disabled module keeps its routes and a new module has none until `php artisan optimize` runs again. See [Deploy an application that uses modules](/laravel-modules/guides/production/deploy/).

Both the repository and the manifest are container singletons. The manifest reuses the `module.json` data the repository already read, so each file is read once per application. Each test application starts with an empty scan.

### Why `Module::resetModules()` exists

The scan cache lives per repository instance for the lifetime of one PHP process. A new web request or `php artisan` invocation always starts fresh. Long-lived processes, such as queue workers or tests, keep their scan and go stale if module folders change underneath them. Call `Module::resetModules()` to clear the cache so the next call rescans disk.

The package calls `resetModules()` automatically after `module:make`, `module:delete`, `module:install`, and `addLocation()`. Code that adds or removes module folders directly — such as a test copying a fixture module — must call it manually. Until then, `all()` and `count()` return the previous list. A lookup by name that misses rescans once if the configured scan paths changed.

## What a disabled module costs

The scan reads every `module.json`, including disabled modules. A disabled module costs one file read and one activator lookup. Its providers never load, so it adds no provider registration, routes, views, or migration paths.

Provider registration accounts for most module boot overhead. With 200 generated modules (`php artisan optimize`, Xdebug off, OPcache on), a web request took about 77 ms versus 32 ms with no modules. Most of that gap came from Laravel booting three providers per module. Absolute timings vary by machine, but disabling unused modules removes their providers from every request.

A module provider also skips its config directory scan on web requests when config is cached. See [Deploy an application that uses modules](/laravel-modules/guides/production/deploy/).
