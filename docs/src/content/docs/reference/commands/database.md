---
title: Database commands
description: Artisan commands that run, roll back, reset, refresh and seed module migrations.
---

This page lists the commands that run module migrations and seeders.

## Shared behaviour

Every command on this page extends `BaseCommand`, the package base class of the commands that run one action for each selected module (see [Contracts and base classes](/laravel-modules/reference/api/contracts/#basecommand)). The behaviour below applies to all of them. The full description is in [Module management commands](/laravel-modules/reference/commands/module-management/#shared-behaviour).

### Arguments and options

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `module` (argument) | | none | One or more module names. Optional, variadic. |
| `--all` | `-a` | `false` | Run the action for every module. |

### Module selection

- With names, the command runs the action once per name.
- With `--all`, or with `All` chosen in the interactive prompt, a command that has a `--direction` option uses the enabled modules ordered by the `priority` field of `module.json` in that direction. `module:migrate-refresh` has no `--direction` option and uses the enabled modules in ascending order. The interactive list shows the same modules.
- `--direction` has no effect on modules given by name. A module given by name runs whatever its status: `module:migrate-refresh Shop` refreshes a disabled `Shop`.
- With no module and no interactive input, the command prints `No module given. Pass a module name or --all.` and exits with `1`.
- An unknown module name prints `Module [Nope] does not exist.` (several names: `Modules [Nope, Typo] do not exist.`) and the command exits with `1` before any action runs. `module:migrate-fresh` does not wipe the database.

### Exit status

An action that fails does not stop the remaining modules. The command exits with `1` if the action failed for at least one module.

### Confirmation and prohibition

`module:migrate-fresh`, `module:migrate-refresh`, `module:migrate-reset` and `module:migrate-rollback` are confirmable commands: they have a `--force` option, they ask for confirmation in the `production` environment, and `Module::prohibitDestructiveCommands()` prohibits them. See [ConfirmableCommand](/laravel-modules/reference/api/contracts/#confirmablecommand) for the exact behaviour, and [Destructive commands](/laravel-modules/reference/api/repository/#destructive-commands) for the prohibition.

### Migration paths

A module migration path is the `migration` generator path (`paths.generator.migration.path`, default `database/migrations`) inside the module, or the `migration.path` value of its `module.json`. A module also uses the paths that Laravel's migrator received from inside the module folder (see below).

### Module migrations and `php artisan migrate`

Two commands run module migrations: Laravel's `php artisan migrate` and `module:migrate`.

`php artisan migrate` runs the pending migrations of the application and of every enabled module in one pass, sorted by file name, in one batch. A module folder reaches Laravel's migrator by two registrations. Both add the folder `paths.generator.migration.path` of the module, and the migrator keeps each path once:

- `LaravelModulesServiceProvider` adds the folder of each enabled module when `auto-discover.migrations` is `true` (the default).
- The `boot()` method of `ModuleServiceProvider` calls `loadMigrationsFrom()` with the same folder. The provider that `module:make` generates extends `ModuleServiceProvider`, and the package registers the providers of enabled modules only.

Neither registration reads `migration.path` of `module.json`, and a disabled module is not registered.

`module:migrate` calls `migrate --path=<module paths> --realpath` once for each selected module, so it runs only the migrations of those modules, each module in its own batch. It adds:

- the selection of modules by name or with `--all`, which uses the order of `--direction`;
- the migrations of a disabled module: the module folder is always passed;
- the `migration.path` value of `module.json`;
- `--subpath`, to run a directory, a file or a glob pattern of the module;
- `--seed`, to run `module:seed` after the migrations.

With `auto-discover.migrations` set to `false`, `php artisan migrate` still runs the migrations of each enabled module whose provider extends `ModuleServiceProvider`, through `loadMigrationsFrom()`. It skips an enabled module without such a provider, for example a module created with `module:make --plain`, or one whose providers extend `Illuminate\Support\ServiceProvider`. `module:migrate` is not affected.

## module:migrate

Runs the migrations of the given modules through Laravel `migrate`.

```bash
php artisan module:migrate [<module>...] [-d|--direction=DIRECTION] [--database=DATABASE] [--pretend] [--force] [--seed] [--subpath=SUBPATH] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--direction` | `-d` | `asc` | Order of the modules selected with `--all`. |
| `--database` | | none | Database connection. |
| `--pretend` | | `false` | Dump the SQL queries instead of running them. |
| `--force` | | `false` | Run in production without the Laravel confirmation. |
| `--seed` | | `false` | Run `module:seed` for the module after its migrations. |
| `--subpath` | | none | A directory, a file or a glob pattern, relative to the module migration path. |

**Notes**

- This command is not confirmable. It passes `--force` to `migrate`, which does its own production confirmation. See [Module migrations and `php artisan migrate`](#module-migrations-and-php-artisan-migrate).
- For each module the command calls `migrate --path=<module paths> --realpath` with `--database`, `--pretend` and `--force` when given.
- A `--subpath` that matches no directory and no file prints `Migration subpath [<subpath>] does not exist in module [<name>].` and counts as a failure.
- `--seed` calls `module:seed <module>` with `--database` and `--force`.

```bash
php artisan module:migrate Blog --seed
```

## module:migrate-fresh

Drops all tables of the database, then runs the application migrations and the migrations of the given modules.

```bash
php artisan module:migrate-fresh [<module>...] [-d|--direction=DIRECTION] [--database=DATABASE] [--seed] [--pretend] [--drop-views] [--drop-types] [-a|--all] [--force]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--direction` | `-d` | `asc` | Order of the modules selected with `--all`. |
| `--database` | | none | Database connection. |
| `--seed` | | `false` | Run `module:seed` for each module after its migrations. |
| `--pretend` | | `false` | Not supported. The command refuses to run. |
| `--drop-views` | | `false` | Drop the views too. |
| `--drop-types` | | `false` | Drop the types too (PostgreSQL only). |
| `--force` | | `false` | Skip the production confirmation. |

**Notes**

- `--pretend` prints `--pretend is not supported by module:migrate-fresh: it would still drop all tables.` and exits with `1` before anything runs.
- The command is confirmable. See [Confirmation and prohibition](#confirmation-and-prohibition).
- Steps, in order: validate the module names; confirm; `db:wipe` (silent, forced); `migrate:install`; `migrate` for the migration paths outside `paths.modules` and for `database/migrations`; `module:migrate` with `--force` for each selected module.
- `db:wipe` drops every table of the connection, not only the tables of the selected modules. The modules not selected are not migrated again.
- The command stops with `1` when `db:wipe`, `migrate:install` or the root `migrate` fails.

```bash
php artisan module:migrate-fresh --all --seed --force
```

## module:migrate-refresh

Resets and runs again the migrations of the given modules.

```bash
php artisan module:migrate-refresh [<module>...] [--database=DATABASE] [--seed] [-a|--all] [--force]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--database` | | none | Database connection. |
| `--seed` | | `false` | Run `module:seed` for each module at the end. |
| `--force` | | `false` | Skip the production confirmation. |

**Notes**

- The command is confirmable. See [Confirmation and prohibition](#confirmation-and-prohibition).
- It has no `--direction`, `--pretend` or `--subpath` option. `--all` selects the enabled modules in ascending order of `priority`.
- For each module the command runs `module:migrate-reset`, `module:migrate` and, with `--seed`, `module:seed`, each with `--force`. It stops the module at the first call that fails.

```bash
php artisan module:migrate-refresh Blog --seed
```

## module:migrate-reset

Rolls back every migration of the given modules that ran.

```bash
php artisan module:migrate-reset [<module>...] [-d|--direction=DIRECTION] [--database=DATABASE] [--pretend] [--subpath=SUBPATH] [-a|--all] [--force]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--direction` | `-d` | `desc` | Order of the modules selected with `--all`. |
| `--database` | | none | Database connection. |
| `--pretend` | | `false` | Print the SQL queries instead of running them. |
| `--subpath` | | none | A directory, a file or a glob pattern, relative to the module migration path. |
| `--force` | | `false` | Skip the production confirmation. |

**Notes**

- The command is confirmable. See [Confirmation and prohibition](#confirmation-and-prohibition).
- It prints `Rollback: <migration>` for each migration, or `Nothing to rollback on module <name>`.
- A `--subpath` that matches nothing prints an error and counts as a failure.

```bash
php artisan module:migrate-reset Blog --pretend
```

## module:migrate-rollback

Rolls back the last batch of migrations of the given modules.

```bash
php artisan module:migrate-rollback [<module>...] [-d|--direction=DIRECTION] [--database=DATABASE] [--pretend] [--subpath=SUBPATH] [-a|--all] [--force]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--direction` | `-d` | `desc` | Order of the modules selected with `--all`. |
| `--database` | | none | Database connection. |
| `--pretend` | | `false` | Print the SQL queries instead of running them. |
| `--subpath` | | none | A directory, a file or a glob pattern, relative to the module migration path. |
| `--force` | | `false` | Skip the production confirmation. |

**Notes**

- The command is confirmable. See [Confirmation and prohibition](#confirmation-and-prohibition).
- The last batch is the highest batch number among the migrations of the module (and the subpath, when given).
- It prints `Rollback: <migration>` for each migration, or `Nothing to rollback on module <name>`.

```bash
php artisan module:migrate-rollback Blog
```

## module:migrate-status

Shows which migrations of the given modules ran.

```bash
php artisan module:migrate-status [<module>...] [-d|--direction=DIRECTION] [--database=DATABASE] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--direction` | `-d` | `asc` | Order of the modules selected with `--all`. |
| `--database` | | none | Database connection. |

**Notes**

- The command calls Laravel `migrate:status --path=<module paths> --realpath` for each module.
- It uses the same paths as `module:migrate`, `module:migrate-rollback` and `module:migrate-reset`.

```bash
php artisan module:migrate-status Blog
```

## module:seed

Runs the seeders of the given modules through Laravel `db:seed`.

```bash
php artisan module:seed [<module>...] [-d|--direction=DIRECTION] [--class=CLASS] [--database=DATABASE] [--force] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--direction` | `-d` | `asc` | Order of the modules selected with `--all`. |
| `--class` | | none | Class name of the root seeder. |
| `--database` | | none | Database connection. |
| `--force` | | `false` | Pass `--force` to `db:seed`. |

**Notes**

- This command is not confirmable. `db:seed` does its own production confirmation.
- The seeders of a module are, in order of lookup: the classes listed in the `migration.seeds` array of `module.json`; else `<namespace>\<Module>\<seeder namespace>\<Module>DatabaseSeeder` (`namespace` is `modules.namespace`, the seeder namespace comes from the `seeder` generator path); else the same class name under the last folder of each `scan.paths` entry.
- A class listed in `migration.seeds` that does not exist prints a warning and is skipped. A module without a seeder prints a warning and does not count as a failure.
- With `--class`, the command replaces the short class name of each seeder found with the given class name, in the same namespace.
- A seeder that throws an exception is reported through the exception handler and counts as a failure.
- Each module starts with a header line, `Seeding <Name> Module ......`, followed by the output of `db:seed`. Warnings follow that output.
- When every seeder of a module ran, the command prints `INFO  Module [Blog] seeded.`.
- When a `db:seed` call failed, threw an exception, or was cancelled at the production confirmation, the command prints `ERROR  Module [Blog] seeding failed.` instead, and the exit status is 1. The other modules are still seeded.

```bash
php artisan module:seed Blog --class=PostSeeder
```
