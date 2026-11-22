---
title: How modules boot
description: The path from the package service provider to the routes of a module, where module providers run among your own, what is cached, and what a disabled module costs.
---

This page follows one request through the package: from `LaravelModulesServiceProvider` to a module route. It explains what the package reads, in which order, and what it keeps. For the order of modules, see [Control the load order of modules](/laravel-modules/guides/modules/control-the-load-order/).

## The sequence

```text
LaravelModulesServiceProvider::register()
  1. bind the repository, the activator and the module manifest;
     queue the stub base path; register the console, migration,
     translation and event-discovery hooks; merge the package config
  registerModules()
    2. scan the module.json files, keep the enabled modules, sort by priority
    3. register the class aliases
    4. load the providers through ProviderRepository (bootstrap/cache/modules.php):
       register() of each module provider, and of the providers it lists
    5. include the files
Laravel boots all providers
  6. boot() of each ModuleServiceProvider:
     commands, schedules, translations, config, views, migrations
     the route provider of the module maps its route files
```

### 1. Bindings and hooks

Laravel discovers the package service provider. In `register()` it binds three singletons: the module repository (`RepositoryInterface`, alias `modules`), the activator (`ActivatorInterface`) and the `ModuleManifest`. The activator class comes from `modules.activator` and `modules.activators`. The bindings are lazy, so nothing is scanned yet.

The package also queues a callback that sets the stub base path when the application has booted, so it sees config that other providers set during registration. See [Generators and stubs](/laravel-modules/concepts/generators-and-stubs/#how-published-stubs-take-over).

### 2. Scan, filter and sort

`registerModules()` asks the `ModuleManifest` for the data of the enabled modules. The manifest:

1. Finds every `module.json` in the scan paths. By default that is `modules.paths.modules`, plus the paths of `modules.scan.paths` when `modules.scan.enabled` is true. The package appends `/*` to a path that does not end with it and reads `<path>/module.json`, so the default entry `vendor/*/*` finds `vendor/acme/blog/module.json`. See [Load modules from extra locations](/laravel-modules/guides/modules/scan-extra-locations/).
2. Keeps the modules for which the activator says "enabled".
3. Sorts them by `priority` in `module.json` (a missing priority counts as `0`). The sort is ascending, so a lower number registers first. Modules with equal priority keep the order of the scan: the scan paths in their configured order, and within one path the module folders in alphabetical order.

The result is a list of provider classes, plus aliases and files, in priority order. A module found only by scanning has no entry in the statuses file, so it counts as disabled until you enable it. Its classes must autoload before you enable it: an enabled module whose provider class cannot be loaded stops every `artisan` command.

### 3. Aliases

Before the providers load, the manifest registers the `aliases` of the enabled modules with Laravel's alias loader. When two modules declare the same alias, the one that comes later in priority order wins.

### 4. Providers and the providers manifest

The package passes the provider list to Laravel's `ProviderRepository`, the class Laravel uses for `bootstrap/cache/services.php`. It writes its own manifest next to it, `bootstrap/cache/modules.php`, and rebuilds it when the list of providers changes. It separates deferred providers, which Laravel registers only when their services are needed, from eager ones, which it registers at once.

The file is always named `modules.php` and sits in the cache folder of Laravel. On Laravel Vapor, where `bootstrap/cache` is read-only, `modules.vapor_maintenance_mode` moves it next to the config cache. The file holds the provider list. It does not hold the scan. See [What is cached](#what-is-cached).

No command deletes the file, and `php artisan optimize:clear` does not remove it. `module:disable` leaves the module in the file until the next request rewrites it, because the package rebuilds the file only when the list of providers differs from the list it holds.

`ProviderRepository` calls `register()` on each eager provider while the package provider is still registering. The `ModuleServiceProvider` of a module first merges the config of the module, then registers the providers it lists in `$providers`, all from its own `register()`. By default those are a route provider and an event provider. They can read the module config in their `register()`.

### 5. Files

After every provider of every enabled module has registered, the manifest includes each module's `files` with `include_once`. A helper file listed in `files` is therefore loaded once per request, after all module providers have registered.

### 6. Boot

Laravel calls `boot()` on all providers once every provider has registered. The `ModuleServiceProvider` of a module registers its commands and schedules, translations, views and migrations. It has merged its config before, in `register()`. The route provider that the generator adds extends Laravel's `RouteServiceProvider`, which loads the route files of the module. The reference page [Module service provider](/laravel-modules/reference/api/module-service-provider/) lists each step.

For the filter in step 2, see [Module status and activators](/laravel-modules/concepts/status-and-activators/).

## Where module providers run among your own

The package loads the module providers from its own `register()`. Laravel registers the providers of installed packages before the providers listed in your application (`bootstrap/providers.php`). Module providers therefore sit at the position of this package among the discovered packages, ahead of your application providers. Two consequences follow:

- A module `register()` runs before the `register()` of your `App\Providers` classes. When both bind the same abstract, your binding is registered later and wins.
- A module `boot()` runs before the `boot()` of your application providers.
- The module merges its config files in `register()`, before it registers its `$providers`. The module config is therefore available in the `register()` of its own sub-providers and of every provider that registers after it, and in the `boot()` of every provider. The merge keeps what is already under the module's config key: a copy you published to `config/`, and values that providers registered before the module set. Those values win over the module files. Values that your providers set in `register()` or `boot()` come after the merge, so they replace the module's.
- A provider that registers before the module provider, such as a package that Laravel discovers before this one, cannot read the module config in its `register()`. Read it in `boot()`.

Other packages that Laravel discovers before this one are registered first. The order among discovered packages is Laravel's, not the package's.

The older `Module::boot()` and `Module::register()` methods did this work themselves. The package no longer calls them, and they are [deprecated](/laravel-modules/upgrading/deprecations/).

## What is cached

The package keeps two things, and they are separate.

| Cache | Where | Lifetime | Holds |
| --- | --- | --- | --- |
| Providers manifest | `bootstrap/cache/modules.php` | On disk until the provider list changes | The providers of the enabled modules, as Laravel's `ProviderRepository` stores them. |
| Scan | Memory, in the repository and manifest instances | One PHP process | The decoded `module.json` files, and the `Module` objects built from them. |

The package writes no other cache file. The scan is not written to disk, and there is no `module:cache` command. A cache file saved about 2 ms at 200 modules in the maintainers' measurement, and it adds the risk of a stale cache after a module change. Earlier versions skipped the in-memory scan cache in tests, so each `module_path()` call re-read every `module.json`, and the cost grew with the square of the module count (3.3 s per test at 200 modules, 249 ms after the change in 13.3.0).

Laravel's route cache is a third cache, and it is not the package's. With cached routes, a disabled module keeps its routes and a new module has none until `php artisan optimize` runs again. See [Deploy an application that uses modules](/laravel-modules/guides/production/deploy/).

Both the repository and the manifest are container singletons. The manifest builds its data from the `module.json` files the repository already read, so each file is read once per application. A test application starts with an empty scan.

### Why `Module::resetModules()` exists

The scan is cached per repository instance, inside one PHP process. A new process, such as the next web request or the next `php artisan` call, always rescans. A process that lives longer, such as a queue worker or a test, keeps the scan it made, and that scan goes stale when module folders change under it. `resetModules()` forgets the scan, so the next call reads the files again.

The package calls it after `module:make`, `module:delete`, `module:install` and `addLocation()`. Code that creates or removes a module folder some other way, for example a test that copies a fixture module into place, must call it itself. Until then `all()` and `count()` return the old list. A lookup by name that misses rescans once when the scan paths changed.

## What a disabled module costs

The scan reads every `module.json`, including those of disabled modules. A disabled module costs a file read and an activator lookup. Its providers are never loaded, so it adds no provider registration, no route and no migration path to the request.

Provider registration is where most of the time goes. The maintainers measured 200 generated modules with `php artisan optimize`, Xdebug off and opcache enabled: a web request took about 77 ms, against about 32 ms with no modules. Most of the difference was Laravel booting three providers per module (the main, route and event providers). The figures depend on the machine. The ratio is what carries over: with many modules, disabling the ones you do not need removes their providers from that cost.

A module provider skips its config scan on a web request when the config is cached. See [Deploy an application that uses modules](/laravel-modules/guides/production/deploy/).
