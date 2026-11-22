---
title: Generators and stubs
description: How a module:make command turns a stub into a file, how paths and namespaces come from config, and how published stubs take over.
---

Every `module:make*` command writes files from templates. This page explains the mechanism and the reasons for its parts. For the steps to change a template, see [Customise the generator stubs](/laravel-modules/guides/customise/stubs/). For the stub list and tokens, see [Stubs](/laravel-modules/reference/stubs/) and [Configuration](/laravel-modules/reference/configuration/).

## From command to file

A file generator extends `Make\GeneratorCommand` and answers two questions:

1. Where does the file go? `getDestinationFilePath()` returns a path. The command builds it from the module folder plus a path it reads from `config('modules.paths.generator.<key>')`. For a controller the key is `controller`.
2. What is in the file? `getTemplateContents()` renders a stub, usually `Stub::create('/controller.stub', [...])->render()`, with an array of replacements.

`GeneratorCommand::handle()` does the shared work. It rejects an empty name, an absolute path and a `..` segment, creates the folder, writes the file, and returns an error when the file exists and `--force` is not set. Each concrete command only supplies the path and the contents.

The commands are small because the variation is in data. A stub is a text file, and a path is a config value.

## Stubs and tokens

A stub is a file with the extension `.stub` that holds the output with tokens in it. A token is an upper-case name between dollar signs:

```php
namespace $CLASS_NAMESPACE$;

class $CLASS$
{
}
```

`Stub::getContents()` upper-cases each key of the replacements array, wraps it in `$`, and replaces all tokens in one pass. A replacement value that contains `$TOKEN$` is written as it is. It is never replaced again. This makes the output independent of the order of the replacements.

A stub can also hold conditional sections between `%%START_TAG%%` and `%%END_TAG%%` lines. The generator keeps or drops a section, for example the web or API routes in the route provider, depending on what the module has.

For `module:make`, the keys of each stub come from `modules.stubs.replacements`. A key can name a built-in token (`STUDLY_NAME`, `LOWER_NAME`, `MODULE_NAMESPACE`) or map to a class that computes the value. In JSON stubs the package escapes built-in values for JSON. It inserts a value from a custom class as it is. See [Replacement tokens](/laravel-modules/guides/customise/replacement-tokens/).

## Paths and namespaces

The path and the namespace of a generated class come from the same config entry, `modules.paths.generator.<key>`:

```php
'controller' => ['path' => 'app/Http/Controllers', 'generate' => true],
```

`Support\Config\GenerateConfigReader` reads the entry into a `GeneratorPath` object. The namespace is derived, not stored. The reader removes the app folder from the path (`app/Http/Controllers` becomes `Http/Controllers`), makes each segment StudlyCase and joins the result to the module namespace. For the module `Blog` that gives `Modules\Blog\Http\Controllers`. An entry may set `namespace` to override the derived value.

The path is the single source for both, and this has a reason. A class autoloads only when its namespace matches its folder, through the PSR-4 entry in the module `composer.json`. If the namespace were a second setting, the two could drift, and the generated class would not load. Deriving one from the other keeps them in step.

`module:make` guards the same rule. It checks that every class generator path sits inside `paths.app_folder`, because the module `composer.json` maps the namespace to that folder. With an empty app folder, it checks that each path equals its own StudlyCase namespace. It stops before it writes anything when a path fails. See [Anatomy of a module](/laravel-modules/concepts/module-anatomy/).

### Why paths are configurable

Teams lay out code differently. One wants `app/Services`, another `src/Domain`, another `Application/Actions`. A fixed layout would force the package to choose. With config, the package ships a default and any team can move it, and the namespace follows. The cost is that a custom layout needs matching changes in the app folder and the generator paths. See [Change the folder layout](/laravel-modules/guides/modules/change-the-folder-layout/).

## What `generate` does

Each entry has a `generate` flag. It applies to `module:make` only: it decides which folders and files a new module starts with. It does not stop you from running `module:make-controller` or any other command later. Those commands create the folder when they need it.

A flag also owns some stub files. With `'assets' => ['generate' => false]`, `module:make` writes neither the asset folder nor `vite.config.js` and `package.json`, because those files exist only to build the assets. The same holds for `routes`, `views` and `config`, which own their stub files. Stubs that no generator key owns, such as `composer`, are always written.

`module:make --plain` skips the stub files and the resources. `--api` and `--inertia` change which controller and views the module gets.

## How published stubs take over

The package ships its stubs in `src/Commands/stubs/`. To change one, publish them:

```bash
php artisan vendor:publish --tag=stubs
```

This copies them to `stubs/nwidart-stubs` in your application. The copy does nothing until you point the package at it. `modules.stubs.enabled` must be `true` and `modules.stubs.path` must hold the folder. The package sets the base path once, after every provider has registered. With `enabled` at its default of `false`, the package ignores a custom path.

The lookup is per file. `Stub::getPath()` joins the base path and the stub name. When that file does not exist, it falls back to the stub in the package. So a published folder can hold only the stubs you changed, and the rest still work.

The fallback has a price. A published copy never changes when the package changes. After an upgrade, new tokens and conditional sections in a stub do not reach your copy until you merge them in. See [Changes in 13.x](/laravel-modules/upgrading/13-x-release-notes/) for a case: the 13.2 notes list the markers that a published route provider and module provider stub must carry to get the new behaviour. Old copies keep working as before.
