---
title: Deprecations
description: Every deprecated class, method and config key of laravel-modules, since which version, and what replaces it.
---

These items carry `@deprecated` in the source or in `config/config.php`. They remain in 13.x and are removed in v14. The package itself never calls them. Calls in your code still run, though several do nothing, never fire, or throw an error.

Version 13.2.0 first marked most of these deprecated ([13.2.0 release notes](/laravel-modules/upgrading/13-x-release-notes/#deprecated-removed-in-v14)). Post-13.3.0 changes on `master` added `FileRepository::$stubPath`, `getStubPath()`, `setStubPath()`, and the Lumen classes.

The package stopped calling the boot and register methods earlier. Since 11.1.0, `ModuleManifest` registers modules directly and `BootstrapServiceProvider` no longer runs. Search your app and modules for the names below; any match belongs to your code.

`Module` below refers to `Nwidart\Modules\Module` ("the module instance"). The `Module` facade (`Nwidart\Modules\Facades\Module`) is not deprecated.

| Item | Deprecated since | Removed in | Replacement |
| --- | --- | --- | --- |
| `boot()` on the module instance | 13.2.0 | v14 | Nothing to call. Each module boots through its `ModuleServiceProvider`. |
| `register()` on the module instance | 13.2.0 | v14 | Nothing to call. `LaravelModulesServiceProvider` registers enabled modules via `ModuleManifest`. |
| `registerTranslation()` on the module instance | 13.2.0 | v14 | `ModuleServiceProvider::registerTranslations()`, or `modules.auto-discover.translations`. Only the deprecated `boot()` calls it. |
| `isLoadFilesOnBoot()` on the module instance | 13.2.0 | v14 | Nothing. `ModuleManifest::registerFiles()` includes `files` from `module.json`. |
| `FileRepository::boot()` and `register()` | 13.2.0 | v14 | Nothing to call. |
| `RepositoryInterface::boot()` and `register()` | 13.2.0 | v14 | Nothing to call. Remove them from custom repositories in v14. |
| `ModuleEvent::BOOT` | 13.2.0 | v14 | Nothing. Only the deprecated `boot()` fires it, so it never fires. |
| `ModuleEvent::REGISTER` | 13.2.0 | v14 | Nothing. Only the deprecated `register()` fires it, so it never fires. |
| `FileRepository::$stubPath`, `getStubPath()`, and `setStubPath()` | after 13.3.0 | v14 | `modules.stubs.path` in `config/modules.php` (or `Stub::setBasePath()`). The package never reads `$stubPath`. |
| `LumenModulesServiceProvider`, `Lumen\LumenFileRepository`, and `Lumen\Module` | after 13.3.0 | v14 | Use Laravel with `LaravelModulesServiceProvider` (`Laravel\LaravelFileRepository` and `Laravel\Module`). Registering `LumenModulesServiceProvider` throws `Unresolvable dependency resolving [Parameter #1 [ <required> array $paths ]] in class Nwidart\Modules\ModuleManifest` since 11.1.0. Lumen has no Laravel 13 release. |
| `Traits\MigrationLoaderTrait` | 13.2.0 | v14 | `Nwidart\Modules\Migrations\Migrator`. The trait is unused; calling `getMigrationGeneratorPath()` or `loadMigrationFiles()` throws a `TypeError`. |
| Config key `modules.register.translations` | 13.2.0 | v14 | `ModuleServiceProvider::registerTranslations()`, or `modules.auto-discover.translations`. Read only by the deprecated `boot()`, so it has no effect. Remove it from `config/modules.php`. |
| Config key `modules.register.files` | 13.2.0 | v14 | Nothing. Read only by the deprecated `boot()` and `register()`, so it has no effect. Remove it from `config/modules.php`. |
| `Providers\ContractsServiceProvider` | 13.2.0 | v14 | Nothing. It binds nothing and is no longer registered. `LaravelModulesServiceProvider` binds `RepositoryInterface` directly. |

A module that relied on `Module::boot()` to load translations needs a `ModuleServiceProvider` or `'auto-discover' => ['translations' => true]`.
