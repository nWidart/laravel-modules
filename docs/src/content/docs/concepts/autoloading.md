---
title: Autoloading and the Composer merge plugin
description: Why the package uses wikimedia/composer-merge-plugin, what the merge does with a module composer.json, and the trade-off against one PSR-4 entry.
---

Module classes load through Composer. This page explains how, and why the package does it with a plugin. For the setup steps, see [Installation](/laravel-modules/start-here/installation/).

## The problem

A module is a folder with classes in a namespace such as `Modules\Blog\`. PHP finds those classes through the Composer autoloader, so Composer must know the namespace and its folder. There are two ways to tell it.

### One entry for all modules

The approach before version 11 was a single PSR-4 entry in the application `composer.json`:

```json
"autoload": {
    "psr-4": {
        "Modules\\": "Modules/"
    }
}
```

It needs no plugin, and a new module needs no change to `composer.json`, because every module is a folder below `Modules/`. The cost is that the application decides how every module is laid out. The namespace must match the folder tree exactly, from `Modules/` down. A module cannot keep its classes in an `app/` subfolder, because `Modules\Blog\Http\Controllers\X` would then have to live in `Modules/Blog/Http/Controllers/X.php`. A module also cannot carry its own autoload rules, such as extra namespaces for factories and tests, and cannot be moved out as a package unchanged.

### One entry per module, merged

Since version 11 the entry above is no longer needed. Each module has its own `composer.json` with its own `autoload` map, and the application merges them. The plugin `wikimedia/composer-merge-plugin` does the merge. The application lists the module files in its own `composer.json`:

```json
"extra": {
    "merge-plugin": {
        "include": [
            "Modules/*/composer.json"
        ]
    }
}
```

When Composer builds the autoloader, the plugin reads every file that matches the glob and adds its sections to the root configuration. The package requires the plugin, so it installs with the package. Composer asks you once to trust it, and the setting `allow-plugins` records the answer. With the plugin set to `false`, modules are not autoloaded.

## What the merge does with a module `composer.json`

| Section | Effect |
| --- | --- |
| `autoload` | Merged. The namespaces of the module become namespaces of the application. |
| `autoload-dev` | Merged by default (`merge-dev`). |
| `require`, `require-dev` | Merged by default, so a module can declare its packages. |
| `extra`, `scripts` | Not merged unless you enable `merge-extra` or `merge-scripts` in the plugin configuration. |

These rules belong to the plugin. See its documentation for the full list.

The module `composer.json` that `module:make` generates maps `Modules\Blog\` to the app folder, and gives the factories, seeders and tests their own entries. See [Anatomy of a module](/laravel-modules/concepts/module-anatomy/).

### What `module:update` adds

`module:update` runs `composer require` for the `require` entries of a module and `composer require --dev` for its `require-dev`, then copies the `scripts` of the module into the application `composer.json`. The plugin merges `require` when Composer resolves, and the command is the explicit route. It reads `modules.composer.composer-output` to decide whether Composer prints its output.

## Why `composer dump-autoload` is needed

Composer writes the autoload map to `vendor/composer/` when it runs. A new module is invisible until that map is rebuilt, so run `composer dump-autoload` after you add a module by hand, change a module `composer.json` or copy a module into the folder.

`module:make` runs `composer dump-autoload` itself once it has generated the modules. If that command fails, `module:make` prints Composer's message and tells you to fix the module and run it again. `module:dump` runs `composer dump-autoload` inside one module or all of them.

The source comments say the run is there "to discover new service providers": the package registers the classes listed in `module.json`, and each class must autoload.

## The trade-off

| | Single `Modules\` entry | Merge plugin |
| --- | --- | --- |
| Setup | One line in `composer.json` | A plugin and an `include` entry |
| New module | No `composer.json` change | `composer dump-autoload`, which `module:make` runs |
| Layout | Fixed: namespace equals folder tree | Free: each module maps its own namespaces |
| Module as a package | Needs a rewrite | Its `composer.json` already works |
| Extra dependency | None | `wikimedia/composer-merge-plugin`, trusted in `allow-plugins` |

The plugin runs code during Composer operations, so some teams prefer not to trust it. The sources do not document a setup without it. The single entry maps a namespace to a folder one to one, and the default module layout puts classes in `app/`, so that entry does not match the default layout.

## Related

- [Install modules](/laravel-modules/guides/distribute/install-modules/) covers modules that arrive through Composer or `module:install`.
- [Change the location and namespace](/laravel-modules/guides/modules/change-the-location-and-namespace/) covers a module folder other than `Modules/`.
