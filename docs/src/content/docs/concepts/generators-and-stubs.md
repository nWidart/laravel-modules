---
title: Generators and stubs
description: How module:make commands render stubs, how paths derive namespaces, and how published stubs override defaults.
---

Every `module:make*` command writes files from templates. This page explains how generators, paths, and stubs work together. To customise templates, see [Customise the generator stubs](/laravel-modules/guides/customise/stubs/). For stub names and tokens, see [Stubs and placeholders](/laravel-modules/reference/stubs/) and [Configuration](/laravel-modules/reference/configuration/).

## From command to file

Each file generator answers two questions: where the file goes and what it contains. `Make\GeneratorCommand` handles the shared workflow:

- rejects empty names, absolute paths, and `..` segments;
- creates parent folders;
- writes the file, or returns an error when it exists and `--force` is not passed.

The destination combines the module folder with `config('modules.paths.generator.<key>')` (for example, `controller`). The contents come from rendering a stub with a replacement map.

Keeping paths in config and templates in stub files keeps every generator command small and consistent.

## Stubs and tokens

A stub is a `.stub` template containing uppercase tokens wrapped in dollar signs:

```php
namespace $CLASS_NAMESPACE$;

class $CLASS$
{
}
```

The renderer replaces all tokens in one pass. A replacement value containing `$TOKEN$` stays untouched and is never substituted again, so output never depends on replacement order.

Stubs can also contain conditional sections that the generator keeps or drops based on module state (such as web or API routes in the route provider). See [Stubs and placeholders](/laravel-modules/reference/stubs/#placeholder-syntax) for both syntaxes.

For `module:make`, each stub's tokens come from `modules.stubs.replacements`. Entries can name built-in tokens or map to a class that computes dynamic values. See [Add your own stub placeholders](/laravel-modules/guides/customise/replacement-tokens/).

## Paths and namespaces

The path and namespace of a generated class come from one config entry, `modules.paths.generator.<key>`:

```php
'controller' => ['path' => 'app/Http/Controllers', 'generate' => true],
```

`Support\Config\GenerateConfigReader` derives the namespace from `path` rather than storing it twice. It strips `paths.app_folder` (`app/Http/Controllers` becomes `Http/Controllers`), converts each segment to StudlyCase, and prepends the module namespace. For `Blog`, that produces `Modules\Blog\Http\Controllers`. Set `namespace` on an entry to override the derived value.

Deriving the namespace from the path keeps PSR-4 autoloading in sync. If the two were separate settings, they could drift and generated classes would fail to load.

`module:make` enforces this rule before writing any files. It checks that every class generator path sits inside `paths.app_folder`, or matches its StudlyCase namespace when `app_folder` is empty. See [Anatomy of a module](/laravel-modules/concepts/module-anatomy/).

### Why paths are configurable

Different teams prefer different folder layouts, such as `app/Services`, `src/Domain`, or `Application/Actions`. Configurable generator paths let you pick a layout while keeping derived namespaces in step. See [Change the folder layout of modules](/laravel-modules/guides/modules/change-the-folder-layout/).

## What `generate` does

Each generator entry has a `generate` flag that applies only to `module:make`. It chooses which folders and starter files a new module gets. Running `module:make-controller` or other generators later still creates folders on demand.

Four generator keys (`assets`, `routes`, `views`, and `config`) also own stub files in `stubs.files`. For example, setting `paths.generator.assets.generate` to `false` skips `resources/assets`, `vite.config.js`, and `package.json`. Stubs not owned by a key (such as `composer`) ignore these flags and are skipped only if removed from `stubs.files` or when passing `--plain`.

When `generate` is `true`, `module:make` also generates the main service provider, route provider, event provider, master seeder, and controller. Passing `--plain` skips all stub files and starter classes and removes the main provider from `module.json`. Flags like `--api` and `--inertia` swap the controller and views generated.

## How published stubs take over

Default stubs ship in `src/Commands/stubs/`. Published copies take effect only when `stubs.enabled` is `true` and `stubs.path` points to your custom folder. See [Customise the generator stubs](/laravel-modules/guides/customise/stubs/).

Keeping `stubs.enabled` separate lets `stubs.path` hold a default `vendor/` path without activating custom stubs. The package resolves the stub base path in a `booted()` callback so it sees config changed by other providers during registration.

Stub lookup runs per file. When a stub is missing from your custom folder, the package falls back to its built-in copy. You only need to keep the stubs you modify.

Published stubs do not update automatically during package upgrades. New tokens or conditional markers will not reach your copies until you merge them in. Check [Changes in 13.x](/laravel-modules/upgrading/13-x-release-notes/) after upgrading to see which stubs changed.
