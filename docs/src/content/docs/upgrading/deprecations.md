---
title: Deprecations
description: Every deprecated class, method and config key of laravel-modules, since which version, and what replaces it.
---

These items are marked `@deprecated` in the source or in `config/config.php`. They still exist in 13.x, and the source states that v14 removes them. The package itself no longer calls any of them, so a call from your code still runs, but some of the items do nothing, are never fired, or throw a `TypeError`. The table says which.

Version 13.2.0 is the first release that marks them as deprecated ([13.2.0 release notes](/laravel-modules/upgrading/13-x-release-notes/#deprecated-removed-in-v14)). The package stopped calling the boot and register methods earlier: since 11.1.0 the package registers modules through `ModuleManifest` and no longer registers `BootstrapServiceProvider`, the class that called `FileRepository::boot()` and `register()`. Search your application and modules for the names below. The package does not call them, so a match is your own code.

`Module` below is the class `Nwidart\Modules\Module`, and "the module instance" is an object of that class. The `Module` facade (`Nwidart\Modules\Facades\Module`) is not deprecated.

| Item | Deprecated since | Removed in | Replacement |
| --- | --- | --- | --- |
| `boot()` on the module instance | 13.2.0 | v14 | Nothing to call. Each module boots through its own `ModuleServiceProvider`. |
| `register()` on the module instance | 13.2.0 | v14 | Nothing to call. The package service provider registers the providers and files of enabled modules through `ModuleManifest`. |
| `registerTranslation()` on the module instance | 13.2.0 | v14 | `ModuleServiceProvider::registerTranslations()`, or `modules.auto-discover.translations`. Only the deprecated `boot()` calls it. |
| `isLoadFilesOnBoot()` on the module instance | 13.2.0 | v14 | Nothing. `ModuleManifest::registerFiles()` loads the `files` of `module.json`. |
| `FileRepository::boot()` and `register()` | 13.2.0 | v14 | Nothing to call. |
| `RepositoryInterface::boot()` and `register()` | 13.2.0 | v14 | Nothing to call. Remove them from a custom repository when v14 lands. |
| `ModuleEvent::BOOT` | 13.2.0 | v14 | Nothing. Only the deprecated `boot()` fires it, so the event never fires. |
| `ModuleEvent::REGISTER` | 13.2.0 | v14 | Nothing. Only the deprecated `register()` fires it, so the event never fires. |
| `Traits\MigrationLoaderTrait` | 13.2.0 | v14 | `Nwidart\Modules\Migrations\Migrator`. The trait is unused, and calling `getMigrationGeneratorPath()` or `loadMigrationFiles()` throws a `TypeError`. |
| Config key `modules.register.translations` | 13.2.0 | v14 | `ModuleServiceProvider::registerTranslations()`, or `modules.auto-discover.translations`. Only the deprecated `boot()` reads the key, so it has no effect. Remove it from `config/modules.php`. |
| Config key `modules.register.files` | 13.2.0 | v14 | Nothing. Only the deprecated `boot()` and `register()` read the key, so it has no effect. Remove it from `config/modules.php`. |
| `Providers\ContractsServiceProvider` | 13.2.0 | v14 | Nothing. It binds nothing and is no longer registered. The package service provider registers `RepositoryInterface` as a singleton. |

A module that relied on `boot()` on the module instance to load translations needs a `ModuleServiceProvider` that does it, or `'auto-discover' => ['translations' => true]`.
