---
title: "Optional nwidart/laravel-actuator integration: a modules indicator and module-shipped indicators, added to config at boot"
author: Nwidart
date: 2026-11-22
branch: feat/actuator-health
id: l-db792d73
scope: repo
status: active
src: agent
touches:
  - src/Actuator/HealthIndicators.php
  - src/Actuator/ModulesHealthIndicator.php
  - src/Activators/FileActivator.php
  - src/LaravelModulesServiceProvider.php
  - src/Support/ModuleServiceProvider.php
  - src/Commands/Make/HealthIndicatorMakeCommand.php
  - config/config.php
---

## Decided
- The actuator is a `suggest` and `require-dev`, never `require`. `Actuator\HealthIndicators::register()` is the one place that guards and merges: it returns unless `getProviders(ActuatorServiceProvider::class) !== []` and `modules.actuator.enabled` (missing or null counts as true). `::class` does not autoload, so nothing from `Nwidart\Actuator` loads without the actuator.
- Registration runs in `boot()` (package provider and `ModuleServiceProvider::boot()`): every provider has registered, so the actuator config is merged. The whole `actuator.health.indicators` array is written back (`$app + $ours`), so application entries win and keys with dots work.
- Module indicators are named `module:{nameLower}:{key}`; a module without `$healthIndicators` returns before any check.
- `ModulesHealthIndicator` reads only what the repository and activator already hold: `RepositoryInterface::all()` and `FileActivator::getStatuses()` (new, public). `ActivatorInterface` was not changed, so other activators skip the missing-on-disk check. Stored names are matched to modules by lower case name, like `FileRepository::find()`.

## Tried and ruled out
- `providerIsLoaded()` as the guard: O(1), but not on the `Application` contract the provider holds.
- Reporting an invalid `module.json` or a module provider that cannot be loaded: both crash the boot before any request, so the indicator never runs. A tolerant boot (skip and report broken modules) was deferred.

## Watch out
- `config:cache` bakes the registered indicators into the cached config, since registration happens during the boot that `config:cache` runs. With a cached config a cached entry counts as an application entry and wins: a changed definition, a removed indicator or `modules.actuator.enabled => false` only take effect after `config:cache` again. New names are still added at boot (registration does not skip cached config).
- Testbench ignores package discovery here, so the actuator provider is absent in `BaseTestCase`; tests that need it add `ActuatorServiceProvider` to `getPackageProviders()`.
