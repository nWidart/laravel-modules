---
title: Module status and activators
description: Why the enabled or disabled status of a module is not stored in module.json, what the file activator keeps, and what a status changes.
---

Every module is enabled or disabled. This page explains where the status lives, why, and what the status changes at runtime. For the commands, see [Enable and disable modules](/laravel-modules/guides/modules/enable-and-disable-modules/).

## Why the status is not in `module.json`

Before version 6, a module kept its status in its own `module.json`. Version 6 moved it out. The 6.0.0 entry of the changelog states the reason: it lets the status live somewhere other than a file, for example a database.

Two things follow from keeping the status out of the manifest:

- `module.json` describes the module, and the status describes this installation. The same module can be enabled in production and disabled on a developer machine, without a change to the module.
- A module that arrives from elsewhere, for example from a package, is turned on or off without rewriting its manifest.

The package reads status through an interface, `ActivatorInterface`, so the storage is replaceable. Version 6 shipped the interface together with the file activator.

## What the file activator stores

The default activator, `FileActivator`, keeps one JSON file that maps module names to booleans. It loads the file once when it is created and rewrites it on each change. The default location is `modules_statuses.json` at the application root, so that the file is visible and can be committed. The file format, the path setting and the failure cases are in [FileActivator](/laravel-modules/reference/api/contracts/#fileactivator) and [Configuration](/laravel-modules/reference/configuration/#activators).

A missing or empty file means no statuses. A file that is not valid JSON, or that holds a non-empty JSON list, throws an `InvalidJsonException`. The activator does not treat a corrupt file as empty, because the next write would replace it and lose every status.

### A missing entry means disabled

`hasStatus()` returns `false` for "enabled" when the module has no entry. A module that the scan finds but the file does not list is disabled.

The default is the safe one. A folder that appears under `Modules/` is not active code until someone enables it. A module that the scan finds in an extra location, for example under `vendor/` or `plugins/`, has no entry either, so it stays disabled until you enable it, and its classes must autoload before you do. See [Load modules from extra locations](/laravel-modules/guides/modules/scan-extra-locations/). `module:make` writes the entry itself, as enabled unless you pass `--disabled`, and `module:delete` removes the entry together with the folder.

### Commit the file, or keep it per environment

Whether the file belongs in version control depends on whether every environment should run the same modules.

| | File committed | File kept per environment |
| --- | --- | --- |
| What you get | The set of enabled modules is reviewed and deployed with the code. A fresh clone runs the same modules as production. | Each environment chooses its own modules, for example a module disabled only on a developer machine. |
| What a deploy does | Nothing to do: the file arrives with the code. | The file must exist on the server before the first request. |
| When the file is missing | Not applicable. | Every module is disabled: the application boots without module routes, views or config, and no error says so. |
| Cost | A local `module:disable` shows up as a change in `git status`, and two branches that enable different modules conflict on the same file. | Nothing in the repository says which modules production runs. |

Either way, a missing file after a fresh clone or a deploy leaves every module disabled. See [Enable and disable modules](/laravel-modules/guides/modules/enable-and-disable-modules/) for the commands and [Deploy an application that uses modules](/laravel-modules/guides/production/deploy/) for where the file fits in a deploy. That guide recommends committing it.

## What a status changes

The status is read when the package registers the modules. See [How modules boot](/laravel-modules/concepts/how-modules-boot/).

| Part of the module | Enabled | Disabled |
| --- | --- | --- |
| Providers in `module.json` | Registered | Not registered |
| Routes, views, config, translations, schedules | Registered by the providers | Absent, because the providers are absent |
| Commands in the provider `$commands` | Registered | Not registered |
| Migrations | Added to `php artisan migrate`, by the provider and by `auto-discover.migrations` | Not added |
| `aliases` and `files` of `module.json` | Registered and included | Skipped |

With cached routes, a status change does not reach the routes until `php artisan optimize` runs again: a disabled module keeps its routes, and a newly enabled one has none.

Commands that take a module name act on a disabled module as well: `module:migrate Blog` finds the module by name. Commands that take `--all` with a `--direction` option use the enabled modules only, and the others use every module. See [Database commands](/laravel-modules/reference/commands/database/).

### What a status does not change

The status does not touch autoloading. The classes of a disabled module still load when code asks for them, because Composer maps the namespace from the module `composer.json` regardless of status. See [Autoloading and the Composer merge plugin](/laravel-modules/concepts/autoloading/).

Disabling a module therefore does not make its classes unreachable. Code in another module that imports a class from a disabled module works until it needs something that only the providers set up, such as a view namespace or a config key.

Event discovery is the same. By default Laravel discovers listeners only in `app/Listeners`, and the `$shouldDiscoverEvents` setting of the generated module `EventServiceProvider` has no effect, so module listeners are mapped in `$listen` (see [Use events and listeners in a module](/laravel-modules/guides/console-and-events/events-and-listeners/)). Where you opt in to discovery, it is Laravel's: the package only teaches it to turn a file under `Modules/` into the right class name, and adds no discovery path. If your application lists a module listener directory in `withEvents(discover: [...])`, for example `Modules/*/app/Listeners`, Laravel scans that glob whether or not the module is enabled. A listener found there runs, if its class autoloads, even though the module's providers are not registered.

Treat the status as "is this module wired into the application", and not as an access control.

## What a disabled module costs

A disabled module costs its `module.json` read and one lookup in the statuses file. See [How modules boot](/laravel-modules/concepts/how-modules-boot/#what-a-disabled-module-costs).

## When a custom activator makes sense

The file activator suits most applications. A custom activator is worth writing when:

- the status must differ per environment, tenant or customer, and a file in the repository cannot express that;
- several application servers must agree on one status, and a file on each server cannot;
- the status must be changed from an admin interface and audited.

A custom activator implements `Contracts\ActivatorInterface` and is listed under `modules.activators`, with a `class` key. `modules.activator` names the one in use. The package throws `InvalidActivatorClass` when the name is unknown, the `class` key is missing or the class does not exist.

The status is read at boot, on every request. A custom activator that queries a database adds that query to every request, and it needs the database to be reachable that early. For how to write one, see [Store module status with a custom activator](/laravel-modules/guides/customise/custom-activator/).
