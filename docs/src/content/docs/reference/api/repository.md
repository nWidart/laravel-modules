---
title: Module facade and repository
description: Every public method of the Module facade and of the repository behind it, with signature, return type and behaviour.
---

This page lists the public methods of the `Module` facade and of the repository it calls. To work with a single module, see [Module class](/laravel-modules/reference/api/module/).

## Where the methods come from

| Name | Class or value |
| --- | --- |
| Facade | `Nwidart\Modules\Facades\Module`. The package registers the alias `Module`. |
| Facade accessor | `modules` |
| Interface | `Nwidart\Modules\Contracts\RepositoryInterface`. The container binds it as a singleton and aliases it as `modules`. |
| Abstract class | `Nwidart\Modules\FileRepository`. It implements `RepositoryInterface` and `Countable`, and uses `Macroable`. |
| Bound class | `Nwidart\Modules\Laravel\LaravelFileRepository`. It adds no methods to `FileRepository`. |

The facade forwards every call to the bound repository, so it accepts every public method of `FileRepository`, not only the ones in the facade docblock.

```php
use Nwidart\Modules\Facades\Module;

$blog = Module::find('blog');
```

`RepositoryInterface` declares only part of the methods below. Its docblock names five more methods (`setUsed`, `forgetUsed`, `getUsedNow`, `update` and `resetModules`) that the package commands call on the bound repository. `FileRepository` implements them. A custom repository must implement them too. The tables mark methods that are not in the interface.

A module is an instance of `Nwidart\Modules\Module`. The repository indexes modules by the lower-case `name` of their `module.json`.

## Find and list

| Signature | Returns | Description |
| --- | --- | --- |
| `all()` | `array<string, Module>` | Every module, keyed by lower-case name. Calls `scan()`. |
| `scan()` | `array<string, Module>` | Finds every `module.json` in the scan paths and returns the modules, keyed by lower-case name. The result is kept for the repository instance. See [Scan cache](#scan-cache). |
| `toCollection()` | `Nwidart\Modules\Collection<string, Module>` | `scan()` result as a [Collection](#collection). |
| `collections(bool\|int $status = 1)` | `Collection<string, Module>` | The modules with the given status, as a `Collection`. Not in the interface. |
| `allEnabled()` | `array<string, Module>` | The enabled modules, in scan order. |
| `allDisabled()` | `array<string, Module>` | The disabled modules, in scan order. |
| `getByStatus(bool\|int $status)` | `array<string, Module>` | The modules whose status equals `$status`. With the default `FileActivator`, a module that has no stored status counts as disabled. |
| `getOrdered(string $direction = 'asc')` | `array<string, Module>` | The enabled modules only, sorted by the `priority` field of `module.json`. `'desc'` reverses the order. Any other value sorts ascending. |
| `count()` | `int` | The number of modules in the `all()` list. |
| `find(string $name)` | `Module\|null` | The module with that name, or `null`. See [Name matching](#name-matching). |
| `findOrFail(string $name)` | `Module` | Like `find()`. Throws [`ModuleNotFoundException`](/laravel-modules/reference/api/exceptions/#modulenotfoundexception) with the message `Module [<name>] does not exist!`. |
| `has($name)` | `bool` | `true` when `find()` returns a module. Not in the interface. |
| `getModuleJsonFiles()` | `list<array{path: string, json: Json}>` | Every `module.json` found in the scan paths, each decoded once, in glob order. `ModuleManifest` reads this list. Not in the interface. |

### Name matching

`find()` matches the name without regard to case. When no module matches, it tries the studly-case form of the name, so `blog-post` and `blog_post` find the module `BlogPost`.

When a lookup finds nothing and the scan paths changed since the last scan, `find()` scans again once and looks again.

## Status

| Signature | Returns | Description |
| --- | --- | --- |
| `isEnabled(string $name)` | `bool` | `true` when the module is enabled. Throws `ModuleNotFoundException` for an unknown module. |
| `isDisabled(string $name)` | `bool` | The opposite of `isEnabled()`. Throws `ModuleNotFoundException` for an unknown module. |
| `enable(string $name)` | `void` | Calls `Module::enable()` on the module. Throws `ModuleNotFoundException` for an unknown module. Not in the interface. |
| `disable(string $name)` | `void` | Calls `Module::disable()` on the module. Throws `ModuleNotFoundException` for an unknown module. Not in the interface. |

Enabling and disabling fire the module events. See [Events](/laravel-modules/reference/api/events/). With the default `FileActivator`, a module that has no stored status counts as disabled.

## Paths and assets

| Signature | Returns | Description |
| --- | --- | --- |
| `getPath()` | `string` | The folder that holds the modules: the `$path` given to the constructor, else `modules.paths.modules`, else `base_path('Modules')`. |
| `getPaths()` | `array<int, string>` | The extra module folders added with `addLocation()`. Not in the interface. |
| `addLocation(string $path)` | `static` | Adds a module folder and calls `resetModules()`. Not in the interface. |
| `getScanPaths()` | `array<int, string>` | Every scanned folder, each ending in `/*`: extra locations, `getPath()`, and `modules.scan.paths` (when `modules.scan.enabled` is `true`). |
| `getModulePath(string $moduleName)` | `string` | The module folder, with a trailing `/`. For a missing module, returns `<getPath()>/<StudlyName>/`. Never throws. |
| `assetPath(string $module)` | `string` | `modules.paths.assets`, `/`, and the module name as given. |
| `getAssetsPath()` | `string` | `modules.paths.assets`. Not in the interface. |
| `asset(string $asset)` | `string` | Protocol-relative URL (`//...`) for `module:path`. Throws [`InvalidAssetPath`](/laravel-modules/reference/api/exceptions/#invalidassetpath) without `:`. Text before the first `:` is the module name; the rest is the path (which may contain `:`). Not in the interface. |
| `getFiles()` | `Illuminate\Filesystem\Filesystem` | The filesystem instance. |
| `config(string $key, mixed $default = null)` | `mixed` | `config('modules.'.$key, $default)`. |

```php
Module::asset('blog:css/app.css'); // //example.test/modules/blog/css/app.css
```

## The used module

`module:use` stores one module name in the file `storage/app/modules/modules.used`. The name stays until `module:unuse` deletes the file, so it applies to every later command, not to one terminal session. Generator commands read it when no module is given. See [`module:use`](/laravel-modules/reference/commands/module-management/#moduleuse). The methods are not in the interface.

| Signature | Returns | Description |
| --- | --- | --- |
| `setUsed($name)` | `void` | Writes the module name to `storage/app/modules/modules.used` and fires the `used` event. Throws `ModuleNotFoundException` for an unknown module. |
| `forgetUsed()` | `void` | Deletes the `modules.used` file and fires the `unused` event on the module that was in use. It takes no argument. It fires no event when no module is in use or the stored name matches no module. See [`module:unuse`](/laravel-modules/reference/commands/module-management/#moduleunuse). |
| `getUsedNow()` | `string` | The studly-case name of the used module. Throws `ModuleNotFoundException` when no module is used. |
| `getUsedStoragePath()` | `string` | The path of `modules.used`. Creates the folder and an empty file when they do not exist. |

## Install, update and delete

| Signature | Returns | Description |
| --- | --- | --- |
| `delete(string $name)` | `bool` | Deletes the module with `Module::delete()`, then calls `resetModules()`. Returns the result of the directory removal. Throws `ModuleNotFoundException` for an unknown module. |
| `install(string $name, string $version = '', string $type = 'composer', bool $subtree = false)` | `Symfony\Component\Process\Process` | Runs the installer and returns the finished process. An empty `$version` installs `dev-master` with `composer` and `master` with `git`. Not in the interface. |
| `update(string $module)` | `void` | Runs [`module:update`](/laravel-modules/reference/commands/module-management/#moduleupdate) for one module: `composer require` for `require`, `composer require --dev` for `require-dev`, and merges `scripts` into the root `composer.json`. Does not run `composer update`. Not in the interface. |

## Stubs and cache

| Signature | Returns | Description |
| --- | --- | --- |
| `getStubPath()` | `string\|null` | Deprecated, removed in v14. Returns the `setStubPath()` value, else `modules.stubs.path` when `modules.stubs.enabled` is `true`, else `null`. Unused by the package; generators use `Stub::setBasePath()`. See [Stubs and placeholders](/laravel-modules/reference/stubs/). |
| `setStubPath(string $stubPath)` | `static` | Deprecated, removed in v14. Stores the stub path for `getStubPath()`. |
| `resetModules()` | `static` | Forgets the scan result. The next call scans the `module.json` files again. Not in the interface. |

### Scan cache

`scan()` and `ModuleManifest` keep their results per instance, not in static properties. Both are container singletons, so an application reads each `module.json` once. A new test application starts with an empty cache.

| Operation | Effect on the cache |
| --- | --- |
| `delete()`, `addLocation()`, `module:make`, `module:delete`, `module:install` | Clear it. |
| `resetModules()` | Clears it. |
| `find()` that misses, when the scan paths changed | Clears it and scans once more. |
| `all()`, `count()`, `toCollection()`, `allEnabled()` and similar | Return the cached list. They do not detect module folders added or removed outside the repository. |

The scan result is not refreshed when a module folder is added or removed outside the repository: `Module::resetModules()` forgets it.

## Macros

`FileRepository` uses `Illuminate\Support\Traits\Macroable`. It has the static methods `macro()`, `mixin()`, `hasMacro()` and `flushMacros()`, and forwards calls to unknown methods to the macros. The package registers no macros.

```php
use Nwidart\Modules\FileRepository;

FileRepository::macro('names', fn () => array_keys($this->all()));

Module::names(); // ['blog', 'shop']
```

## Destructive commands

```php
Module::prohibitDestructiveCommands(bool $prohibit = true): void
```

A static method of the facade, not a repository method. It is the package counterpart of Laravel's `DB::prohibitDestructiveCommands()`. It calls `prohibit()` on five commands, and `false` allows them again:

| Command class | Command |
| --- | --- |
| `MigrateFreshCommand` | `module:migrate-fresh` |
| `MigrateRefreshCommand` | `module:migrate-refresh` |
| `MigrateResetCommand` | `module:migrate-reset` |
| `MigrateRollbackCommand` | `module:migrate-rollback` |
| `ModuleDeleteCommand` | `module:delete` |

Each of these commands has its own flag, so you can prohibit one command and leave the others. A prohibited command prints `This command is prohibited from running in this environment.` and exits with status `1`. `--force` does not lift the prohibition. See [ConfirmableCommand](/laravel-modules/reference/api/contracts/#confirmablecommand).

```php
// In the boot() method of a service provider.
Module::prohibitDestructiveCommands($this->app->isProduction());
```

To prohibit a single command, call `prohibit()` on its class:

```php
use Nwidart\Modules\Commands\Database\MigrateFreshCommand;

// In the boot() method of a service provider.
MigrateFreshCommand::prohibit($this->app->isProduction());
```

`module:migrate-refresh` calls `module:migrate-reset`. If only `MigrateResetCommand` is prohibited, the inner reset prints the prohibition message and refresh exits with `1`. Nothing changes in the database.

## Collection

`Nwidart\Modules\Collection` extends `Illuminate\Support\Collection`. `toCollection()` and `collections()` return it.

| Signature | Returns | Description |
| --- | --- | --- |
| `getItems()` | `array<TKey, TValue>` | The items as they are, without conversion. |
| `toArray()` | `array<TKey, mixed>` | Converts each `Module` to the attributes of its `module.json` plus a `path` key. Any other value that is `Arrayable` is converted with its `toArray()`. Other values stay as they are. |

## Deprecated

Both methods are deprecated and removed in version 14. The package no longer calls them. It registers the enabled modules through `ModuleManifest` and each module boots through its own service providers.

| Signature | Returns | Description |
| --- | --- | --- |
| `register()` | `void` | Calls `register()` on each module of `getOrdered()`. |
| `boot()` | `void` | Calls `boot()` on each module of `getOrdered()`. |

## Constructor

```php
new LaravelFileRepository(Container $app, ?string $path = null)
```

The constructor takes the application and an optional module folder. It resolves `UrlGenerator`, the config repository and `Filesystem` from the container. The package binds the repository without a `$path`, so `getPath()` reads `modules.paths.modules` each time it is called.
