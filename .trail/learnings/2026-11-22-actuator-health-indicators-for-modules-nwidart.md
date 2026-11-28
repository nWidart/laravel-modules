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
  - src/Commands/Make/GeneratorCommand.php
  - src/Generators/ModuleGenerator.php
---

## Decided
- The actuator is a `suggest` and `require-dev`, never `require`. `Actuator\HealthIndicators::register()` is the one place that guards and merges: it returns unless `getProviders(ActuatorServiceProvider::class) !== []` and `modules.actuator.enabled` (missing or null counts as true). `::class` does not autoload, so nothing from `Nwidart\Actuator` loads without the actuator.
- Registration runs in `boot()` (package provider and `ModuleServiceProvider::boot()`): every provider has registered, so the actuator config is merged. The whole `actuator.health.indicators` array is written back (`$app + $ours`), so application entries win and keys with dots work.
- Module indicators are named `module:{nameLower}:{key}`; a module without `$healthIndicators` returns before any check.
- `ModulesHealthIndicator` reads only what the repository and activator already hold: `RepositoryInterface::all()` and `FileActivator::getStatuses()` (new, public). `ActivatorInterface` was not changed, so other activators skip the check for stored names the repository does not know. Stored names are matched to modules by lower case name, like `FileRepository::find()`.
- The scan is cached in long-lived processes (Octane, queue workers), so a scanned module counts as present only while its `module.json` exists (one `is_file` per module, no rescan). Gone and enabled (`hasStatus()`, any activator): listed with the missing error, DOWN. Gone and disabled: left out.
- `paths.generator.health` has no entry in `config/config.php`. With one, an app that inherits the package defaults and moves `paths.app_folder` plus its known class paths had `module:make` rejected for a key it never set. The generator falls back to `{app_folder}Health` and namespace `Health`; `health` stays in `ModuleGenerator::APP_FOLDER_GENERATOR_KEYS`, so a path the app configures is still checked.
- `module:make-health-indicator` StudlyCases each segment (`studly_path()`) and builds the namespace from the same segments, so `api/v2/Payments` gives `Health/Api/V2/Payments.php` in `...\Health\Api\V2`.
- `GeneratorCommand::validateNameArgument()` rejects a segment that is `..` once whitespace, hyphens and underscores are removed (`/[\s\-_]+/u`, the characters `Str::studly()` drops), so `. .`, `.-.`, `._.` cannot escape the module in any generator.

## Tried and ruled out
- `providerIsLoaded()` as the guard: O(1), but not on the `Application` contract the provider holds.
- Reporting an invalid `module.json` or a module provider that cannot be loaded: both crash the boot before any request, so the indicator never runs. A tolerant boot (skip and report broken modules) was deferred.

## Watch out
- `config:cache` bakes the registered indicators into the cached config, since registration happens during the boot that `config:cache` runs. With a cached config a cached entry counts as an application entry and wins: a changed definition, a removed indicator or `modules.actuator.enabled => false` only take effect after `config:cache` again. New names are still added at boot (registration does not skip cached config).
- Other generators still run `Str::studly()` on the whole name (`api/v2/x` gives folder `Api/v2`, namespace `Api\V2`), and the base `getClassNamespace()` strips the class name with `str_replace` everywhere in the argument (`Payments/Payments` loses the folder). Not changed in this PR; fixed for every generator on `fix/generator-nested-names` (see the 2026-11-28 learning), and the health indicator now uses the shared helpers.
- Testbench ignores package discovery here, so the actuator provider is absent in `BaseTestCase`; tests that need it add `ActuatorServiceProvider` to `getPackageProviders()`.
