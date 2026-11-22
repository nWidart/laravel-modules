---
title: Module class
description: Every public method of a module instance and of the Json class, with signature, return type and behaviour.
---

This page lists the public methods of one module (`Nwidart\Modules\Module`) and of `Nwidart\Modules\Json`, the class that reads `module.json` and `composer.json`. To find modules, see [Module facade and repository](/laravel-modules/reference/api/repository/).

## Classes

| Class | Description |
| --- | --- |
| `Nwidart\Modules\Module` | Abstract class. Uses `Illuminate\Support\Traits\Macroable`. |
| `Nwidart\Modules\Laravel\Module` | The class the repository creates. It implements the three abstract methods `getCachedServicesPath()`, `registerProviders()` and `registerAliases()`. |
| `Nwidart\Modules\Lumen\Module` | Deprecated class for Lumen, removed in v14. Not covered here. See [Deprecations](/laravel-modules/upgrading/deprecations/). |

The repository creates a module with `new Module(Container $app, string $name, string $path)` and gives it the decoded `module.json`.

```php
use Nwidart\Modules\Facades\Module;

$blog = Module::findOrFail('Blog');

$blog->getPath();   // /var/www/app/Modules/Blog
$blog->isEnabled(); // true
```

## Names

| Signature | Returns | Description |
| --- | --- | --- |
| `getName()` | `string` | The name given to the constructor: the `name` field of `module.json`. |
| `getLowerName()` | `string` | `strtolower()` of the name. |
| `getStudlyName()` | `string` | `Str::studly()` of the name. |
| `getKebabName()` | `string` | `Str::kebab()` of the name. |
| `getSnakeName()` | `string` | `Str::snake()` of the name. |
| `__toString()` | `string` | The studly-case name. |

## Paths

| Signature | Returns | Description |
| --- | --- | --- |
| `getPath()` | `string` | The module folder, without a trailing slash. |
| `setPath(string $path)` | `static` | Replaces the module folder of this instance. Writes nothing to disk. |
| `getExtraPath(?string $path)` | `string` | The module folder, `/` and `$path`. The module folder alone when `$path` is `null` or empty. |
| `getAppPath()` | `string` | The folder given by `modules.paths.app_folder` inside the module, without a trailing slash. The module folder when that folder does not exist. |
| `getCachedServicesPath()` | `string` | The cache file of the module providers: the cached services file of the application with `services.php` replaced by `<snake_name>_module.php`. With `modules.vapor_maintenance_mode`, the cached config file with `config.php` replaced. Abstract in `Module`. |

## module.json and composer.json

| Signature | Returns | Description |
| --- | --- | --- |
| `json(?string $file = null)` | `Nwidart\Modules\Json` | The [Json](#json) object of a file in the module folder. The default file is `module.json`. The object is kept for the instance. |
| `setJson(Json $json, string $file = 'module.json')` | `static` | Stores an already decoded file, so `json()` does not read it again. |
| `get(string $key, mixed $default = null)` | `mixed` | A key of `module.json`. |
| `getComposerAttr(string $key, mixed $default = null)` | `mixed` | A key of the module `composer.json`. |
| `getDescription()` | `string` | The `description` field, or an empty string when it is missing. |
| `getPriority()` | `string` | The `priority` field as a string, or `'0'` when it is missing. |

Both methods look up a top-level key. A key with dots is not split.

```php
$blog->get('providers', []);
$blog->getComposerAttr('name');
$blog->json()->set('priority', 5)->save();
```

## Status

The module asks the activator (see `activators` in [Configuration](/laravel-modules/reference/configuration/#activators)). The module itself stores nothing.

| Signature | Returns | Description |
| --- | --- | --- |
| `isEnabled()` | `bool` | `true` when the activator reports the module as enabled. |
| `isDisabled()` | `bool` | The opposite of `isEnabled()`. |
| `isStatus(bool $status)` | `bool` | `true` when the module has the given status. |
| `setActive(bool $active)` | `void` | Sets the status. It fires no event. |

## Lifecycle

| Signature | Returns | Description |
| --- | --- | --- |
| `enable()` | `void` | Fires `enabling`, enables the module through the activator, fires `enabled`. |
| `disable()` | `void` | Fires `disabling`, disables the module through the activator, fires `disabled`. |
| `delete()` | `bool` | Fires `deleting`, removes the status of the module from the activator, deletes the module folder, fires `deleted`. Returns the result of the folder removal. The repository `delete()` also clears its scan cache. This method does not. |
| `fireEvent(string $event)` | `void` | Dispatches the event `modules.<lowername>.<event>` with the module as payload. See [Events](/laravel-modules/reference/api/events/). |
| `registerAliases()` | `void` | Registers the `aliases` of `module.json` with Laravel's `AliasLoader`. Abstract in `Module`. |
| `registerProviders()` | `void` | Loads the `providers` of `module.json` through Laravel's `ProviderRepository`. Abstract in `Module`. |

A status change takes effect on the next request or command. The package registers the providers of enabled modules when the application boots.

## Assets

| Signature | Returns | Description |
| --- | --- | --- |
| `static getAssets()` | `array<int, mixed>` | The `src` value of each entry point (`isEntry`) in `public/build/manifest.json`. An empty array when the file does not exist or holds no entry point. |

## Macros

`Module` uses `Macroable`: `macro()`, `mixin()`, `hasMacro()`, `flushMacros()`, and forwarding of unknown calls to the macros. The package registers no macros.

```php
use Nwidart\Modules\Module;

Module::macro('isBlog', fn () => $this->getLowerName() === 'blog');
```

## Deprecated

Both methods are deprecated and removed in version 14. The package no longer calls them. Module files and providers are loaded through `ModuleManifest`.

| Signature | Returns | Description |
| --- | --- | --- |
| `register()` | `void` | Registers aliases and providers, includes the `files` of `module.json`, and fires `register`. |
| `boot()` | `void` | Registers the module translations when `modules.register.translations` is `true`, and fires `boot`. It also includes the `files` when `modules.register.files` is `'boot'`. |

## Json

`Nwidart\Modules\Json` reads, changes and writes one JSON file. `Module::json()` returns it. The constructor reads the file at once.

```php
use Nwidart\Modules\Json;

$json = Json::make(base_path('Modules/Blog/module.json'));
$json->get('name'); // Blog
```

| Signature | Returns | Description |
| --- | --- | --- |
| `new Json($path, ?Filesystem $filesystem = null)` | `Json` | Reads and decodes the file. Throws [`InvalidJsonException`](/laravel-modules/reference/api/exceptions/#invalidjsonexception) when the content is not valid JSON, and Laravel's `FileNotFoundException` when the file does not exist. |
| `static make(string $path, ?Filesystem $filesystem = null)` | `static` | Creates an instance. |
| `get(string $key, mixed $default = null)` | `mixed` | A top-level attribute. |
| `__get(string $key)` | `mixed` | `$json->name` is the same as `get('name')`. |
| `set(string $key, mixed $value)` | `static` | Sets an attribute in memory. It does not write the file. |
| `update(array $data)` | `bool` | Merges `$data` over the attributes with `array_merge()` and writes the file. |
| `save()` | `bool` | Writes the attributes to the file as pretty-printed JSON. |
| `getAttributes()` | `array<string, mixed>` | The attributes as an array. |
| `getContents()` | `string` | The raw content of the file. |
| `decodeContents()` | `array<string, mixed>` | Reads the file again and decodes it. Throws `InvalidJsonException` on invalid JSON. |
| `toJsonPretty(?array $data = null)` | `string` | The given data, or the attributes, as pretty-printed JSON. |
| `getPath()` | `string` | The file path. |
| `setPath($path)` | `static` | Changes the file path used by `save()`. |
| `getFilesystem()` | `Illuminate\Filesystem\Filesystem` | The filesystem instance. |
| `setFilesystem(Filesystem $filesystem)` | `static` | Replaces the filesystem instance. |
| `__call(string $method, array $arguments = [])` | `mixed` | Forwards other calls to the attributes collection, for example `$json->has('name')` or `$json->all()`. |
| `__toString()` | `string` | `getContents()`. |
