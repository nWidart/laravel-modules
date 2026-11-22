---
title: How modules boot
description: The path from the package service provider to the routes of a module, what is cached, and what a disabled module costs.
---

This page follows one request through the package: from `LaravelModulesServiceProvider` to a module route. It explains what the package reads, in which order, and what it keeps. For the order of modules, see [Control the load order](/laravel-modules/guides/modules/control-the-load-order/).

## The sequence

```text
LaravelModulesServiceProvider::register()
  1. bind the repository, the activator and the module manifest
  2. register the Artisan commands
  3. hook module migration and translation paths into Laravel
  4. registerModules()
       a. scan the module folders for module.json files
       b. keep the modules the activator marks enabled
       c. sort them by priority
       d. register the class aliases
       e. load their providers
       f. include their files
each module's ModuleServiceProvider
  register(), then boot(): config, views, translations, migrations, routes
```

### 1. Bindings

The package service provider is discovered by Laravel. In `register()` it binds three singletons: the module repository (`RepositoryInterface`, alias `modules`), the activator (`ActivatorInterface`) and the `ModuleManifest`. The activator class comes from `modules.activator` and `modules.activators`. The binding is lazy, so nothing is scanned yet.

The package also registers a `booted()` callback that sets the stub base path. It runs after every provider has registered, so it sees config that other providers set.

### 2. Scan, filter and sort

`registerModules()` asks the `ModuleManifest` for the data of the enabled modules. The manifest:

1. Finds every `module.json` in the scan paths. By default that is `modules.paths.modules`, plus the paths of `modules.scan.paths` when `modules.scan.enabled` is true.
2. Keeps the modules for which the activator says "enabled".
3. Sorts them by `priority` in `module.json` (a missing priority counts as `0`). The sort is ascending, so a lower number registers first.

The result is a list of provider classes, plus aliases and files, in priority order.

### 3. The providers manifest

The package passes the provider list to Laravel's `ProviderRepository`. That class is the one Laravel uses for `bootstrap/cache/services.php`. It writes its own manifest next to it, `bootstrap/cache/modules.php`, and rebuilds it when the list of providers changes. It also separates deferred providers, which Laravel registers only when their services are needed, from eager ones, which it registers at once.

The file is always named `modules.php` and sits in the cache folder of Laravel. On Laravel Vapor, where `bootstrap/cache` is read-only, `modules.vapor_maintenance_mode` moves it next to the config cache.

This file caches the provider list that Laravel needs to register providers. It does not cache the scan. See [What is cached](#what-is-cached).

### 4. Aliases and files

Before the providers load, the manifest registers the `aliases` of the enabled modules with Laravel's alias loader. After they load, it includes each module's `files` with `include_once`. A module that lists a helper file in `files` gets it loaded once per request, after every provider of every enabled module has been registered.

### 5. Each module provider

`ProviderRepository` calls `register()` on each eager provider while the package provider is still registering. Laravel calls `boot()` on all providers later, once the application has booted. The `ModuleServiceProvider` of a module registers its sub-providers in `register()`. In `boot()` it registers commands and schedules, translations, config, views and migrations. The route provider that the generator adds registers the route files. The reference page [Module service provider](/laravel-modules/reference/api/module-service-provider/) lists each step.

## What is cached

Two caches exist, and they are separate.

| Cache | Where | Lifetime | Holds |
| --- | --- | --- | --- |
| Providers manifest | `bootstrap/cache/modules.php` | On disk until the provider list changes | The providers of the enabled modules, as Laravel's `ProviderRepository` stores them. |
| Scan | Memory, in the repository and manifest instances | One application instance | The decoded `module.json` files, and the `Module` objects built from them. |

The scan is not written to disk. The maintainers chose to read `module.json` once per application and to have no `module:cache` command. At 200 modules, a cache file saved about 2 ms and risked a stale cache after a module change. Earlier versions skipped the in-memory cache in tests, so each `module_path()` call re-read every `module.json`. The cost grew with the square of the module count.

Both the repository and the manifest are container singletons. The manifest builds its data from the `module.json` files the repository already read, so each file is read once per application. In tests, each test application starts with an empty scan.

### Why `Module::resetModules()` exists

An in-memory scan goes stale when module folders change under it. `resetModules()` forgets the scan, so the next call reads the files again. The package calls it for you after `module:make`, `module:delete`, `module:install` and `addLocation()`. You call it yourself when you create or remove a module folder without these, for example in a test that copies a fixture module into place. Until then, `all()` and `count()` return the old list. A lookup by name that misses rescans once when the scan paths changed.

## What a disabled module costs

The scan reads every `module.json`, including those of disabled modules. A disabled module costs a file read and an activator lookup. Its providers are never loaded, so it adds no provider registration, no route and no migration path to the request.

Provider registration is where most of the time goes. In a benchmark with 200 modules and `php artisan optimize`, a request took about 77 ms against 32 ms with no modules, and most of the difference was Laravel booting three providers per module. With many modules, disabling the ones you do not need removes their providers from that cost.

Run `php artisan optimize` in production so that config, routes and events are cached. A module provider skips its config scan on a web request when the config is cached. See [Deploy](/laravel-modules/guides/production/deploy/).

## Where the module providers run

The package loads the module providers from its own `register()`, through the provider machinery of Laravel. A module provider therefore follows the normal lifecycle: `register()` for every provider, then `boot()` for every provider. The older `Module::boot()` and `Module::register()` methods did this work themselves. The package no longer calls them, and they are [deprecated](/laravel-modules/upgrading/deprecations/).

See also [Module status and activators](/laravel-modules/concepts/status-and-activators/) for the filter in step 2.
