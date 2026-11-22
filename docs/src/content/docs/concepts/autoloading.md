---
title: Autoloading and the Composer merge plugin
description: Why the package autoloads modules with wikimedia/composer-merge-plugin, what the merge does with a module composer.json, and how it compares with one PSR-4 entry for all modules or one per module.
---

Module classes load through Composer. This page explains how, and why the package does it with a plugin. For the setup, see [Installation](/laravel-modules/start-here/installation/).

## The problem

A module is a folder with classes in a namespace such as `Modules\Blog\`. PHP finds those classes through the Composer autoloader, so Composer must know the namespace and its folder. There are three ways to tell it.

### One PSR-4 entry for all modules

Before version 11, the application `composer.json` held a single entry:

```json
"autoload": {
    "psr-4": {
        "Modules\\": "Modules/"
    }
}
```

It needs no plugin, and a new module needs no change to `composer.json`, because every module is a folder below `Modules/`. The cost is that the application decides how every module is laid out. The namespace must match the folder tree exactly, from `Modules/` down. The default layout puts classes in `app/`, so `Modules\Blog\Http\Controllers\X` lives in `Modules/Blog/app/Http/Controllers/X.php`, and this entry does not find it. A module also cannot carry its own autoload rules, and cannot be moved out as a package unchanged.

### One PSR-4 entry per module, written by hand

You can add one entry per module to the application `composer.json`, for example `"Modules\\Blog\\": "Modules/Blog/app/"`. This works with the default layout and needs no plugin. It costs an edit of the application `composer.json` for every module, and the entry duplicates what the module already says. The `autoload` and `autoload-dev` maps in the module `composer.json` (for factories, seeders and tests) are not read, so you must copy each of them into the application as well.

### One entry per module, merged

Since version 11, each module has its own `composer.json` with its own `autoload` map, and the application merges them. The plugin `wikimedia/composer-merge-plugin` does the merge. The application lists the module files in its `composer.json`:

```json
"extra": {
    "merge-plugin": {
        "include": [
            "Modules/*/composer.json"
        ]
    }
}
```

When Composer builds the autoloader, the plugin reads every file that matches the glob and adds its sections to the root configuration. The package requires the plugin, so it installs with the package. Composer asks once whether to trust it, and `allow-plugins` records the answer. With the plugin set to `false` in `allow-plugins`, Composer does not run it and modules are not autoloaded.

The module `composer.json` that `module:make` generates maps `Modules\Blog\` to the app folder and gives the factories, seeders and tests their own entries. See [Anatomy of a module](/laravel-modules/concepts/module-anatomy/).

## What the merge does with a module `composer.json`

The plugin merges `autoload` and `autoload-dev`, and `require` and `require-dev` too: the `merge-dev` option of the plugin is on by default. A module can therefore declare the packages it needs. The plugin does not merge `extra` and `scripts` unless you enable `merge-extra` or `merge-scripts`. These rules belong to the plugin: see the [plugin README](https://github.com/wikimedia/composer-merge-plugin#readme).

`module:update` covers what the merge leaves out. It copies the `scripts` of a module into the application `composer.json`, and it runs `composer require` for the `require` entries of the module (`--dev` for `require-dev`), so that Composer resolves and records the packages at once instead of at the next `composer update`. See [module:update](/laravel-modules/reference/commands/module-management/#moduleupdate).

## Why the autoloader must be rebuilt

Composer writes the autoload map to `vendor/composer/` when it runs. A module that appears afterwards is invisible until the application map is rebuilt: after you add a module by hand, change a module `composer.json` or copy a module into the folder. `module:make` rebuilds it for you with `composer dump-autoload` in the application root.

`module:dump` does not do this. It runs `composer dump -o -n -q` with the module folder as the working directory, so it acts on the module's own `composer.json` and leaves the application map as it is. See [module:dump](/laravel-modules/reference/commands/module-management/#moduledump).

The module providers listed in `module.json` are classes too, so each must autoload before the package can register it.

### Plain modules

`module:make --plain` writes no `composer.json`. The glob of the plugin matches nothing for that module, so its classes are not autoloaded and it has no provider. It stays that way until you add a `composer.json` with an `autoload` map and list a provider in `module.json`.

## The trade-off

| | One `Modules\` entry | One entry per module, by hand | Merge plugin |
| --- | --- | --- | --- |
| Setup | One line in `composer.json` | One entry per module | A plugin and an `include` entry |
| New module | No `composer.json` change | Edit `composer.json`, rebuild the autoloader | Rebuild the autoloader, which `module:make` does |
| Layout | Namespace equals folder tree: does not match the default `app/` layout | Free | Free: each module maps its own namespaces |
| Module-level autoload (factories, seeders, tests) | Not possible | Copied by hand | Merged from the module |
| Module as a package | Needs a rewrite | Its `composer.json` already works, but the application copy can drift | Its `composer.json` already works |
| Extra dependency | None | None | `wikimedia/composer-merge-plugin`, trusted in `allow-plugins` |

The plugin runs code during Composer operations, so some teams prefer not to trust it. The per-module entries are the way to do without it. The package installs the plugin either way, because it requires it.

## Related

- [Install and update modules](/laravel-modules/guides/distribute/install-modules/) covers modules that arrive through Composer or `module:install`.
- [Change where modules live and their namespace](/laravel-modules/guides/modules/change-the-location-and-namespace/) covers a module folder other than `Modules/`.
