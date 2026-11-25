---
title: Actuator integration
description: The ModulesEndpoint and ModulesHealthIndicator classes registered when nwidart/laravel-actuator is installed.
---

This page describes the classes in `Nwidart\Modules\Actuator` that integrate with [`nwidart/laravel-actuator`](https://github.com/nWidart/laravel-actuator). The actuator package is optional. For setup steps, see [Report module health with the actuator](/laravel-modules/guides/production/health-checks/) and [Expose the modules on the actuator](/laravel-modules/guides/production/actuator-modules-endpoint/).

## ModulesEndpoint

`Nwidart\Modules\Actuator\ModulesEndpoint` is a `final` read-only actuator endpoint with ID `modules`. Its constructor injects `Nwidart\Modules\Contracts\RepositoryInterface`.

### Registration

`LaravelModulesServiceProvider::register()` calls `Actuator::endpoint('modules', ModulesEndpoint::class)` when `Nwidart\Actuator\Actuator` exists (`nwidart/laravel-actuator` 1.1.0 or later). A forced re-register of `LaravelModulesServiceProvider` skips registration to prevent duplicate endpoint IDs.

The endpoint is not exposed by default. Add `modules` to `actuator.exposure.include` in `config/actuator.php` to expose it. `modules.actuator.enabled` does not affect this endpoint.

### Operations

| Method | Route name | HTTP path | Returns |
| --- | --- | --- | --- |
| `modules(): array` | `actuator.modules` | `GET /actuator/modules` | `package` info and all modules, sorted case-insensitively by `Module::getName()`. |
| `module(string $name): ?array` | `actuator.modules-name` | `GET /actuator/modules/{name}` | One module resolved via `RepositoryInterface::find($name)`, or `null` (HTTP 404). |

Both methods carry `#[ReadOperation]`. `module()` marks `$name` with `#[Selector]`. Lookup matches `Module::find()`: case-insensitive, with kebab-case and snake_case matching studly-case names.

### Response schema

`modules()` returns two top-level keys:

| Key | Value |
| --- | --- |
| `package` | `['name' => 'nwidart/laravel-modules', 'version' => string\|null]` from `Composer\InstalledVersions::getPrettyVersion()`. |
| `modules` | List of module objects using the six-field schema below. |

`module()` returns a single module object with the same six fields:

| Field | Type | Source |
| --- | --- | --- |
| `name` | `string` | `Module::getName()` (`name` in `module.json`). |
| `enabled` | `bool` | `Module::isEnabled()`. |
| `version` | `string\|null` | `version` in `module.json` cast to string when it is a non-empty string, integer, or float; otherwise `null`. |
| `description` | `string` | `Module::getDescription()`, or `""` when missing. |
| `priority` | `int` | `(int) Module::getPriority()`, or `0` when missing. |
| `providers` | `list<string>` | String entries of `providers` in `module.json`, re-indexed, or `[]`. |

No filesystem paths, `files`, `alias`, `keywords`, extra `module.json` keys, `composer.json` data, or config values are exposed.

## ModulesHealthIndicator

`Nwidart\Modules\Actuator\ModulesHealthIndicator` implements `Nwidart\Actuator\Health\HealthIndicator`. Its constructor injects `RepositoryInterface` and `ActivatorInterface`.

### Registration

`LaravelModulesServiceProvider::boot()` registers `'modules' => ModulesHealthIndicator::class` in `actuator.health.indicators` when `Nwidart\Actuator\ActuatorServiceProvider` is registered. Set `modules.actuator.enabled` to `false` in `config/modules.php` to skip registration. A missing `modules.actuator.enabled` key counts as `true`. An indicator the application already defines under `modules` is kept.

### `health(): Health`

| Status | Condition |
| --- | --- |
| `UP` | Every enabled module has its `module.json` on disk. Disabled modules do not affect status. |
| `DOWN` | At least one enabled module is missing on disk, or a module was skipped because it is broken (see below). |

`health()` returns details under `['modules' => [...]]`, keyed by module name:

- Present module: `['enabled' => bool]`.
- Missing enabled module: `['enabled' => true, 'error' => 'Enabled in the status store, not found on disk.']`.
- Missing disabled module: omitted from `details`.
- Broken module, with `modules.skip_broken_modules` set to `true`: `['enabled' => bool, 'error' => '<reason>']`. The status is `DOWN`. A module with an invalid `module.json` is listed under its folder name. See [Skip broken modules](/laravel-modules/concepts/how-modules-boot/#skip-broken-modules).

For every module in `RepositoryInterface::all()`, `health()` checks `is_file($module->getExtraPath('module.json'))`. This detects removed folders in long-lived processes with any activator.

When the bound activator is `FileActivator`, `health()` also checks `FileActivator::getStatuses()`. Any module enabled there that the repository never scanned (matched case-insensitively) sets status to `DOWN` with the missing-module error. Custom activators do not expose `getStatuses()`, so unscanned entries are not checked.

## Module health indicators and internal helper

Modules register their own health indicators through `$healthIndicators` on `ModuleServiceProvider`. See [Health indicators](/laravel-modules/reference/api/module-service-provider/#health-indicators).

`Nwidart\Modules\Actuator\HealthIndicators` is marked `@internal` and is not part of the public API. `LaravelModulesServiceProvider` and `ModuleServiceProvider` call it during `boot()` to merge indicators into `actuator.health.indicators`.
