---
title: Deprecations
description: Every deprecated class, method and config key of laravel-modules, since which version, and what replaces it.
---

These items are marked `@deprecated` in the source or in `config/config.php`. They remain in 13.x so that existing code keeps working, and the source states they are removed in v14. The package itself no longer calls any of them.

The docblocks do not name the version that deprecated each item. The 13.2.0 entry of the changelog is the first to list them, so the table gives 13.2.0. The [13.2.0 release notes](/laravel-modules/upgrading/13-x-release-notes/#deprecated-removed-in-v14) list them under "Deprecated (removed in v14)".

| Item | Deprecated since | Removed in | Replacement |
| --- | --- | --- | --- |
| `Module::boot()` | 13.2.0 | v14 | Nothing to call. Each module boots through its own `ModuleServiceProvider`. The package registers enabled modules through `ModuleManifest`. |
| `Module::register()` | 13.2.0 | v14 | Nothing to call. The package service provider registers the providers and files of enabled modules through `ModuleManifest`. |
| `Module::registerTranslation()` | 13.2.0 | v14 | `ModuleServiceProvider::registerTranslations()`, or the config key `modules.auto-discover.translations`. Only the deprecated `Module::boot()` calls it. |
| `Module::isLoadFilesOnBoot()` | 13.2.0 | v14 | Nothing. `ModuleManifest::registerFiles()` loads the `files` of `module.json`. Only the deprecated `Module::boot()` and `Module::register()` call it. |
| `FileRepository::boot()` | 13.2.0 | v14 | Nothing to call. |
| `FileRepository::register()` | 13.2.0 | v14 | Nothing to call. |
| `RepositoryInterface::boot()` and `register()` | 13.2.0 | v14 | Nothing to call. Remove them from a custom repository when v14 lands. |
| `ModuleEvent::BOOT` | 13.2.0 | v14 | Nothing. Only the deprecated `Module::boot()` fires it. |
| `ModuleEvent::REGISTER` | 13.2.0 | v14 | Nothing. Only the deprecated `Module::register()` fires it. |
| `Traits\MigrationLoaderTrait` | 13.2.0 | v14 | `Nwidart\Modules\Migrations\Migrator`. The trait is unused. Calling `getMigrationGeneratorPath()` or `loadMigrationFiles()` throws a `TypeError`. |
| Config key `modules.register.translations` | 13.2.0 | v14 | `ModuleServiceProvider::registerTranslations()`, or `modules.auto-discover.translations`. Only the deprecated `Module::boot()` reads the key. |
| Config key `modules.register.files` | 13.2.0 | v14 | Nothing. The package loads module `files` on register. Only the deprecated `Module::boot()` and `Module::register()` read the key. |
| `Providers\ContractsServiceProvider` | 13.2.0 | "a future major version" in its docblock, v14 in the 13.2.0 notes | Nothing. It binds nothing and is no longer registered. The package service provider registers `RepositoryInterface` as a singleton. |

## What to do

- Search your application and modules for the names above. The package does not call them, so a hit is your own code.
- Remove the `register` key from `config/modules.php`. The keys have no effect.
- A module that relied on `Module::boot()` to load translations needs a `ModuleServiceProvider` that does it, or `'auto-discover' => ['translations' => true]`.
- A custom repository that implements `RepositoryInterface` keeps `boot()` and `register()` until v14.
