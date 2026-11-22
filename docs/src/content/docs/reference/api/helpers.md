---
title: Helpers and Blade directive
description: The module, module_path and module_vite functions, the config_path and public_path fallbacks, the @module Blade directive and the php artisan about entry.
---

This page lists the global functions that `src/helpers.php` defines, the `@module` Blade directive and the entry the package adds to `php artisan about`. Composer loads `src/helpers.php` through the `autoload.files` key of the package.

Each function is defined only when no function of that name exists yet.

## module()

```php
module(string $name, bool $instance = false): bool|Nwidart\Modules\Module
```

Returns the status of a module, or the module.

| Parameter | Description |
| --- | --- |
| `$name` | Module name. Matched like `Module::find()`: case-insensitive, and kebab-case and snake_case names find the studly-case module. |
| `$instance` | `false`: return the status. `true`: return the `Module` instance. |

**Returns**

| Case | Value |
| --- | --- |
| `$instance` is `false` | `true` when the module is enabled, otherwise `false`. |
| `$instance` is `true` | The `Module`. |
| The module does not exist | `false`, whatever `$instance` is. The function catches `ModuleNotFoundException`. |

```php
if (module('Blog')) {
    // Blog exists and is enabled.
}

$blog = module('Blog', true); // Module|false
```

## module_path()

```php
module_path(string $name, string $path = ''): string
```

Returns the folder of a module, or a path inside it.

| Parameter | Description |
| --- | --- |
| `$name` | Module name, matched like `module()`. |
| `$path` | Optional path inside the module. It is joined to the module folder with `DIRECTORY_SEPARATOR`. |

**Returns** the module folder without a trailing slash, followed by the separator and `$path` when `$path` is not empty.

**Throws** [`ModuleNotFoundException`](/laravel-modules/reference/api/exceptions/#modulenotfoundexception) when the module does not exist.

```php
module_path('Blog');                    // /var/www/app/Modules/Blog
module_path('Blog', 'routes/web.php');  // /var/www/app/Modules/Blog/routes/web.php
```

## module_vite()

```php
module_vite(string $module, string $asset, ?string $hotFilePath = null): Illuminate\Foundation\Vite
```

Returns a copy of the application `Vite` instance, set up for the build of one module. The shared instance that `@vite` uses stays unchanged.

| Parameter | Description |
| --- | --- |
| `$module` | Build directory, passed to `useBuildDirectory()`. The generated layout passes `build-<lowername>`. |
| `$asset` | The entry point, passed to `withEntryPoints()`. |
| `$hotFilePath` | Hot file, passed to `useHotFile()`. Default: `storage_path('vite.hot')`. |

**Returns** the configured `Vite` instance. Printing it in a Blade view (`{{ module_vite(...) }}`) outputs the tags.

```blade
{{ module_vite('build-blog', 'resources/assets/js/app.js') }}
```

## config_path()

```php
config_path(string $path = ''): string
```

Returns the application config folder, or a path inside it. The package defines this function only when no function of that name exists yet, for applications that lack it, such as Lumen.

| Parameter | Description |
| --- | --- |
| `$path` | Optional path inside the config folder. It is joined to the folder with `DIRECTORY_SEPARATOR`, as given. |

**Returns** `<base path>/config`, followed by the separator and `$path` when `$path` is not empty.

```php
config_path('blog.php'); // /var/www/app/config/blog.php
```

## public_path()

```php
public_path(string $path = ''): string
```

Returns the application public folder, or a path inside it. The package defines this function only when no function of that name exists yet, for applications that lack it, such as Lumen.

| Parameter | Description |
| --- | --- |
| `$path` | Optional path inside the public folder. It is joined to the folder with `DIRECTORY_SEPARATOR`, after its leading separators are removed. |

**Returns** the `path.public` binding, followed by the separator and `$path` when `$path` is not empty.

```php
public_path('modules/blog'); // /var/www/app/public/modules/blog
```

## @module

The package registers the directive with `Blade::if('module', ...)`. The condition is `module($name)`: `true` when the module exists and is enabled. The directive has the usual `@else`, `@elseif` and `@endmodule` forms.

```blade
@module('Blog')
    <a href="{{ route('blog.index') }}">Blog</a>
@endmodule
```

## php artisan about

`LaravelModulesServiceProvider` adds a section to the `about` command.

| Section | Entry | Value |
| --- | --- | --- |
| `Laravel-Modules` | `Version` | The installed version of `nwidart/laravel-modules`, from Composer. |

```bash
php artisan about
```
