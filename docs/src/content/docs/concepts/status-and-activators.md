---
title: Module status and activators
description: Why module status lives outside module.json, what FileActivator stores, and what disabling a module changes.
---

Every module is enabled or disabled. This page explains where the status lives, why, and what it changes at runtime. For commands, see [Enable and disable modules](/laravel-modules/guides/modules/enable-and-disable-modules/).

## Why the status is not in `module.json`

Before version 6, each module stored its status in `module.json`. Version 6 moved status out of the manifest so it can live elsewhere, such as a database.

Keeping status outside `module.json` gives two benefits:

- `module.json` describes the module, while status describes a specific installation. The same module can be enabled in production and disabled locally without modifying module files.
- Modules installed from packages can be enabled or disabled without editing files in `vendor/`.

The package reads status through `ActivatorInterface`, making the storage replaceable.

## What the file activator stores

The default activator, `FileActivator`, stores a JSON object mapping module names to booleans. It loads the file once on instantiation and rewrites it on each change. By default, it writes to `modules_statuses.json` at the application root so you can inspect and commit it. See [FileActivator](/laravel-modules/reference/api/contracts/#fileactivator) and [Configuration](/laravel-modules/reference/configuration/#activators) for format details and failure cases.

With `nwidart/laravel-actuator` installed, the `modules` health indicator reports every module (enabled and disabled) with `enabled: true` or `false`. It reports `DOWN` when an enabled module is missing from disk. The package also registers the read-only `/actuator/modules` endpoint to inspect module metadata. See [Report module health with the actuator](/laravel-modules/guides/production/health-checks/) and [Expose modules through the actuator](/laravel-modules/guides/production/actuator-modules-endpoint/).

A missing or empty file means no statuses. Invalid JSON or a non-empty JSON list throws `InvalidJsonException`. The activator refuses to treat a corrupt file as empty so a later write cannot wipe existing statuses.

### A missing entry means disabled

`hasStatus()` returns `false` for "enabled" when a module has no entry. Any scanned module absent from the statuses file is disabled.

This default is safe. A folder added under `Modules/` or an extra scan location (such as `vendor/` or `plugins/`) stays inactive until you enable it. Its classes must autoload before you enable it. See [Load modules from extra locations](/laravel-modules/guides/modules/scan-extra-locations/).

`module:make` writes the status entry as enabled unless you pass `--disabled`. `module:delete` removes the status entry along with the folder.

### Commit the file, or keep it per environment

Commit `modules_statuses.json` when every environment should run the same modules. Keep it per environment when deployments differ.

| | File committed | File kept per environment |
| --- | --- | --- |
| What you get | Enabled modules are reviewed and deployed with code. Fresh clones match production. | Each environment chooses its own modules, such as disabling a module locally. |
| What a deploy does | Nothing extra: the file arrives with the code. | The file must exist on the server before the first request. |
| When the file is missing | Not applicable. | Every module is disabled. The app boots without module routes, views, or config, with no error. |
| Cost | A local `module:disable` appears in `git status`. Branches enabling different modules can conflict on this file. | Nothing in the repository records which modules production runs. |

Either way, a missing file leaves every module disabled. See [Enable and disable modules](/laravel-modules/guides/modules/enable-and-disable-modules/) for commands and [Deploy an application that uses modules](/laravel-modules/guides/production/deploy/) for deploy practices. That guide recommends committing the file.

## What a status changes

The package reads module status during registration. See [How modules boot](/laravel-modules/concepts/how-modules-boot/).

| Part of the module | Enabled | Disabled |
| --- | --- | --- |
| Providers in `module.json` | Registered | Not registered |
| Routes, views, config, translations, schedules | Registered by providers | Absent, because providers do not load |
| Commands in provider `$commands` | Registered | Not registered |
| Health indicators in `$healthIndicators` | Registered with `nwidart/laravel-actuator` | Not registered |
| Migrations | Added to `php artisan migrate` by provider and `auto-discover.migrations` | Not added |
| `aliases` and `files` of `module.json` | Registered and included | Skipped |
| Root Vite builds (`vite-module-loader.js`) | Entry points included | Skipped |

With cached routes, status changes do not affect routing until `php artisan optimize` runs again. Disabled modules keep cached routes, and newly enabled ones have none.

Commands that take a module name act on disabled modules too: `module:migrate Blog` finds `Blog` by name. With `--all`, commands that declare `--direction`, plus `module:migrate-refresh` and `module:prune`, run on enabled modules only. Other `--all` commands run on every module, enabled or disabled. See [Database commands](/laravel-modules/reference/commands/database/) and [Module management commands](/laravel-modules/reference/commands/module-management/).

### What a status does not change

Status does not affect Composer autoloading. Classes in a disabled module still load when referenced, because Composer merges each module's `composer.json` regardless of status. See [Autoloading and the Composer merge plugin](/laravel-modules/concepts/autoloading/).

Disabling a module does not block imports of its classes. Code in another module can still instantiate a class from a disabled module until it relies on provider setup, such as a view namespace or config key.

Event discovery works the same way. By default, Laravel discovers listeners only in `app/Listeners`, while a generated module `EventServiceProvider` registers only its `$listen` array. See [Use events and listeners in a module](/laravel-modules/guides/console-and-events/events-and-listeners/).

When you opt in to discovery, Laravel scans the directories you list in `withEvents(discover: [...])`. The package only maps discovered files under `Modules/` to module class names; it adds no discovery paths itself. A glob like `Modules/*/app/Listeners` matches disabled modules too, so their listeners still run if their classes autoload.

Treat module status as "is this module wired into the application", not as an access control boundary.

## What a disabled module costs

A disabled module costs one `module.json` read and one lookup in the statuses file. See [How modules boot](/laravel-modules/concepts/how-modules-boot/#what-a-disabled-module-costs).

## When a custom activator makes sense

The file activator fits most applications. Write a custom activator when:

- status must differ per environment, tenant, or customer;
- multiple application servers must share one status store;
- status must change from an admin interface with an audit trail.

A custom activator implements `Contracts\ActivatorInterface` and is registered under `modules.activators` with a `class` key. `modules.activator` selects the active entry. The package throws `InvalidActivatorClass` if the name is unknown, the `class` key is missing, or the class does not exist.

Status is read at boot on every request. A database-backed activator adds a query to every request and requires the database to be reachable during boot. See [Store module status with a custom activator](/laravel-modules/guides/customise/custom-activator/).
