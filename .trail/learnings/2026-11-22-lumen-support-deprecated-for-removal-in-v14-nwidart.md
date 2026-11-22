---
title: "Lumen support is deprecated for removal in v14; fixing the provider was ruled out"
author: Nwidart
date: 2026-11-22
branch: chore/deprecate-lumen
id: l-3f2a8c64
scope: repo
status: active
src: agent
touches:
  - src/LumenModulesServiceProvider.php
  - src/Lumen/LumenFileRepository.php
  - src/Lumen/Module.php
  - phpstan.neon.dist
---

## Decided
- `LumenModulesServiceProvider`, `Lumen\LumenFileRepository` and `Lumen\Module` carry class-level `@deprecated` tags. Nothing changes at runtime. v14 removes them. This supersedes the 2026-10-25 note that the package supports both Laravel and Lumen.
- Registering `LumenModulesServiceProvider` throws on every boot: `Unresolvable dependency resolving [Parameter #1 [ <required> array $paths ]] in class Nwidart\Modules\ModuleManifest`. The provider binds no `ModuleManifest`. It has been broken since v11.1.0, when module registration moved to `ModuleManifest`. Checked in real Lumen 10.0.4 and 11.2.0 applications.
- Lumen is abandoned on Packagist and archived on GitHub. Its last release, `laravel/lumen-framework` v11.2.0, requires `illuminate/* ^11.0`. There is no Lumen release for Laravel 13.
- No replacement exists in the package: use Laravel with `LaravelModulesServiceProvider`, `Laravel\LaravelFileRepository` and `Laravel\Module`.
- The `config_path()` and `public_path()` fallbacks in `src/helpers.php` stay for now and are not tagged deprecated, because Laravel defines functions with the same names.
- v14 removes: the three classes, the `Laravel\Lumen\Application` imports and union types in `src/Module.php` and `src/FileRepository.php`, the `class.notFound` ignore for `Laravel\Lumen\*` in `phpstan.neon.dist`, `tests/LumenModuleTest.php`, and the two helper fallbacks.

## Tried and ruled out
- Making the provider work. Working around it in a Lumen application turned up ten breakages: no `ModuleManifest` binding, `UrlGenerator` contract not bound, `bootstrapPath()` missing, package config not merged, `path.public` missing, `AliasLoader` missing, `ProviderRepository` missing, `Illuminate\Process\Factory` missing, and generated module providers extend Foundation classes that Lumen lacks. Lumen has no illuminate 13 release and cannot be installed next to Laravel in dev or CI, so no fix could be tested.

## Watch out
- The `Laravel\Lumen\Application` types stay in `src/Module.php` and `src/FileRepository.php` until v14, so the PHPStan ignore must stay too.
