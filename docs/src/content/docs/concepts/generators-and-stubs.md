---
title: Generators and stubs
description: How a module:make command turns a stub into a file, why paths and namespaces come from one config entry, and how published stubs take over.
---

Every `module:make*` command writes files from templates. This page explains the mechanism and the reasons for its parts. For the steps to change a template, see [Customise the generator stubs](/laravel-modules/guides/customise/stubs/). For the stub list and the token syntax, see [Stubs and placeholders](/laravel-modules/reference/stubs/) and [Configuration](/laravel-modules/reference/configuration/).

## From command to file

A file generator is a command that answers two questions: where does the file go, and what is in it. The shared base class, `Make\GeneratorCommand`, does the rest. It rejects an empty name, an absolute path and a `..` segment, creates the folder, writes the file, and returns an error when the file exists and `--force` is not set.

The destination comes from the module folder plus a path that the command reads from `config('modules.paths.generator.<key>')`. For a controller the key is `controller`. The contents come from a stub, rendered with an array of replacements.

The commands are small because the variation is in data. A stub is a text file, and a path is a config value.

## Stubs and tokens

A stub is a file with the extension `.stub` that holds the output with tokens in it. A token is an upper-case name between dollar signs:

```php
namespace $CLASS_NAMESPACE$;

class $CLASS$
{
}
```

The renderer replaces all tokens in one pass. A replacement value that contains `$TOKEN$` is written as it is and is never replaced again, so the output does not depend on the order of the replacements. A stub can also hold conditional sections, which the generator keeps or drops depending on what the module has, for example the web or API routes in the route provider. The syntax of both is in [Stubs and placeholders](/laravel-modules/reference/stubs/#placeholder-syntax).

For `module:make`, the tokens of each stub come from `modules.stubs.replacements`. A key can name a built-in token or map to a class that computes the value. See [Add your own stub placeholders](/laravel-modules/guides/customise/replacement-tokens/).

## Paths and namespaces

The path and the namespace of a generated class come from the same config entry, `modules.paths.generator.<key>`:

```php
'controller' => ['path' => 'app/Http/Controllers', 'generate' => true],
```

`Support\Config\GenerateConfigReader` reads the entry. The namespace is derived, not stored. The reader removes the app folder from the path (`app/Http/Controllers` becomes `Http/Controllers`), makes each segment StudlyCase and joins the result to the module namespace. For the module `Blog` that gives `Modules\Blog\Http\Controllers`. An entry may set `namespace` to override the derived value.

The path is the single source for both, and this has a reason. A class autoloads only when its namespace matches its folder, through the PSR-4 entry in the module `composer.json`. If the namespace were a second setting, the two could drift, and the generated class would not load. Deriving one from the other keeps them in step.

`module:make` guards the same rule. It checks that every class generator path sits inside `paths.app_folder`, because the module `composer.json` maps the namespace to that folder. With an empty app folder, it checks that each path equals its own StudlyCase namespace. It stops before it writes anything when a path fails. See [Anatomy of a module](/laravel-modules/concepts/module-anatomy/).

### Why paths are configurable

Teams lay out code differently. One wants `app/Services`, another `src/Domain`, another `Application/Actions`. A fixed layout would force the package to choose. With config, the package ships a default and any team can move it, and the namespace follows. The cost is that a custom layout needs matching changes in the app folder and the generator paths. See [Change the folder layout of modules](/laravel-modules/guides/modules/change-the-folder-layout/).

## What `generate` does

Each entry has a `generate` flag. It applies to `module:make` only: it decides which folders and files a new module starts with. It does not stop you from running `module:make-controller` or any other command later. Those commands create the folder when they need it.

A flag also owns some stub files. With `paths.generator.assets.generate` set to `false`, `module:make` writes neither the asset folder nor `vite.config.js` and `package.json`, because those files exist only to build the assets. The keys `routes`, `views` and `config` own their stub files in the same way. A stub that no key owns, such as `composer`, ignores the flags. It is skipped only when it is removed from `stubs.files`, or when the module is created with `--plain`.

The flag also decides which files `module:make` creates besides the stubs. When `generate` is `true`, the command runs the generators for the main service provider (`<Module>ServiceProvider`), the route provider, the event provider, the master seeder (`<Module>DatabaseSeeder`) and the controller (`<Module>Controller`). With `--plain` it writes no stub files and none of these classes, and it removes the main provider from `module.json`. `--api` and `--inertia` change which controller and views the module gets.

## How published stubs take over

The package ships its stubs in `src/Commands/stubs/`. A published copy does nothing until you enable it with `stubs.enabled`, and you then point `stubs.path` at the folder. See [Customise the generator stubs](/laravel-modules/guides/customise/stubs/) for the steps.

`stubs.enabled` exists so that `stubs.path` can hold a value without using it. The default `path` points into `vendor/`, and the package ignores `path` until `enabled` is `true`. The package sets the base path when the application has booted, so it sees config that other providers set while registering.

The lookup is per file. When a stub does not exist in the base path, the package falls back to its own copy. So a published folder can hold only the stubs you changed, and the rest still work.

The fallback has a price. A published copy never changes when the package changes. After an upgrade, new tokens and conditional sections in a stub do not reach your copy until you merge them in. The 13.2.0 release notes show a case: a published route provider and module provider stub need new markers to get the new behaviour, and old copies keep working as before. See [Changes in 13.x](/laravel-modules/upgrading/13-x-release-notes/).
