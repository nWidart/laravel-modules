---
title: Community packages
description: Companion and third-party packages that extend laravel-modules, and what each adds beyond the core package.
---

This page lists active packages that extend or integrate with `nwidart/laravel-modules`.

## Suggested companion package

`composer.json` lists this optional companion package under `suggest`:

| Package | What it does | Integration with the core |
| --- | --- | --- |
| [`nwidart/laravel-actuator`](https://github.com/nWidart/laravel-actuator) (`^1.1`) | Adds Spring Boot Actuator-style operational endpoints and health checks to Laravel. | When installed, Laravel Modules registers the `modules` health indicator, per-module `$healthIndicators`, and the read-only `/actuator/modules` endpoint. See [Report module health with the actuator](/laravel-modules/guides/production/health-checks/) and [Expose modules through the actuator](/laravel-modules/guides/production/actuator-modules-endpoint/). |

## Third-party packages

Their authors maintain these packages. This project does not maintain, test, or support them. Check each repository for supported Laravel Modules versions. The "Differs from the core" column reflects each package's README.

| Package | What it does | Differs from the core |
| --- | --- | --- |
| [`mhmiton/laravel-modules-livewire`](https://github.com/mhmiton/laravel-modules-livewire) | Registers Livewire components in modules and adds `module:make-livewire` (single-file and multi-file). | Core has no Livewire integration. This package adds a Livewire generator and registers components under module namespaces, such as `<livewire:core::sfc.post.create />`. |
| [`dcblogdev/laravel-module-generator`](https://github.com/dcblogdev/laravel-module-generator) | Builds modules from custom folder templates with `module:build`. | `module:make` renders the fixed stubs in `modules.stubs.files`. `module:build` copies a chosen template folder (shipping Breeze CRUD templates for web and API) and replaces placeholders in names and contents. Run `module:enable` and `composer dump-autoload` after generating. |

To add a package to this list, see [Contribute and get help](/laravel-modules/ecosystem/contributing-and-support/).
