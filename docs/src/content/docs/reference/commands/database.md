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
- With `--all` (or `All` in the prompt), commands with `--direction` select enabled modules ordered by `priority` in that direction. `module:migrate-refresh` has no `--direction` option and selects enabled modules in ascending order. The interactive list shows the same modules.
- `--direction` has no effect on modules given by name. Named modules run regardless of status (`module:migrate-refresh Shop` refreshes a disabled `Shop`).
- With no module and no interactive input, the command prints `No module given. Pass a module name or --all.` and exits with `1`.
- Unknown names print `Module [Nope] does not exist.` (or `Modules [Nope, Typo] do not exist.`) and exit with `1` before any action runs. `module:migrate-fresh` does not wipe the database.

### Exit status

An action that fails does not stop the remaining modules. The command exits with `1` if the action failed for at least one module.

### Confirmation and prohibition

`module:migrate-fresh`, `module:migrate-refresh`, `module:migrate-reset`, and `module:migrate-rollback` implement `ConfirmableCommand`. They accept `--force`, ask for confirmation in `production`, and respect `Module::prohibitDestructiveCommands()` and per-command `prohibit()` (for example `MigrateFreshCommand::prohibit()`). See [ConfirmableCommand](/laravel-modules/reference/api/contracts/#confirmablecommand) and [Destructive commands](/laravel-modules/reference/api/repository/#destructive-commands).

### Migration paths

A module migration path is `paths.generator.migration.path` (default `database/migrations`) inside the module, or `migration.path` in `module.json`. Module commands also include paths registered on Laravel's migrator inside the module folder.

### Module migrations and `php artisan migrate`

`php artisan migrate` runs pending migrations for the application and every enabled module in one pass and one batch. Enabled module folders (`paths.generator.migration.path`) reach Laravel's migrator through two deduplicated registrations:

- `LaravelModulesServiceProvider`, when `auto-discover.migrations` is `true` (default).
- `ModuleServiceProvider::boot()` via `loadMigrationsFrom()`.

Neither registration reads `migration.path` from `module.json` or includes disabled modules. With `auto-discover.migrations` set to `false`, `php artisan migrate` still runs migrations for enabled modules extending `ModuleServiceProvider`, but skips modules without it (such as `--plain` modules).

`module:migrate` calls `migrate --path=<module paths> --realpath` once per selected module (one batch per module). Unlike `php artisan migrate`, it supports `--all` with `--direction`, disabled modules passed by name, `migration.path` in `module.json`, `--subpath`, and `--seed`. Setting `auto-discover.migrations` to `false` does not affect `module:migrate`.

For migration workflows, see [Run module migrations](/laravel-modules/guides/database/migrations/).

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
| `--all` | `-a` | `false` | Migrate every enabled module. |

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
| `--all` | `-a` | `false` | Migrate every enabled module. |
| `--force` | | `false` | Skip the production confirmation. |

**Notes**

- `--pretend` prints `--pretend is not supported by module:migrate-fresh: it would still drop all tables.` and exits with `1` before anything runs.
- The command is confirmable. See [Confirmation and prohibition](#confirmation-and-prohibition).
- Execution order:
  1. Validate module names.
  2. Confirm in production (unless `--force`).
  3. Run `db:wipe` (silent, forced).
  4. Run `migrate:install`.
  5. Run `migrate` for root `database/migrations` and non-module migration paths.
  6. Run `module:migrate --force` for each selected module.
- `db:wipe` drops every table of the connection, not only the tables of the selected modules. Unselected modules are not migrated again.
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
| `--all` | `-a` | `false` | Refresh every enabled module. |
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
| `--all` | `-a` | `false` | Reset every enabled module. |
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
| `--all` | `-a` | `false` | Roll back every enabled module. |
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
php artisan module:migrate-status [<module>...] [-d|--direction=DIRECTION] [--database=DATABASE] [-a|--all] [--json]
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
| `--all` | `-a` | `false` | Show migration status for every enabled module. |
| `--json` | | `false` | Print the status as one JSON document. See [JSON output](/laravel-modules/reference/commands/#json-output). |

**Notes**

- The command calls Laravel `migrate:status --path=<module paths> --realpath` for each module.
- It uses the same paths as `module:migrate`, `module:migrate-rollback` and `module:migrate-reset`.
- With `--json`, the command follows the [JSON output](/laravel-modules/reference/commands/#json-output) rules and does not call `migrate:status`. It lists the migration files of each module from the same paths, sorted by name, and reads the ran migrations and their batches from the migrations table on the `--database` connection.
- The modules appear in the same order and selection as without `--json` (`--all`, `--direction`).

```bash
php artisan module:migrate-status Blog
php artisan module:migrate-status --all --json | jq '.modules[].migrations[] | select(.ran | not) | .name'
```

**JSON output**

The document is an object with two keys.

| Key | Type | Description |
| --- | --- | --- |
| `modules` | array | One entry per module. |
| `modules[].name` | string | Module name. |
| `modules[].migrations` | array | Migrations of the module, sorted by name. `[]` when the module has none. |
| `modules[].migrations[].name` | string | Migration file name without `.php`. |
| `modules[].migrations[].ran` | boolean | `true` when the migration ran. |
| `modules[].migrations[].batch` | integer or `null` | Batch number when the migration ran, `null` when it is pending. |
| `migration_table` | boolean | `false` when the migrations table does not exist. |

```json
{"modules":[{"name":"Blog","migrations":[{"name":"2026_01_01_000000_create_posts_table","ran":true,"batch":1},{"name":"2026_01_02_000000_create_tags_table","ran":false,"batch":null}]},{"name":"Shop","migrations":[]}],"migration_table":true}
```

- A migration whose `shouldRun()` returns `false` is reported as `ran: false`. Without `--json`, the command shows it as `Skipped`.
- Without a migrations table, the command without `--json` prints `Migration table not found.` and exits with `1`. With `--json`, every migration has `ran: false` and `batch: null`, `migration_table` is `false`, and the exit code is `0`.
- With an unknown module, or with no module given, the command writes the error to standard error, prints `{"modules":[],"migration_table":...}` to standard output, and exits with `1`. Check the exit code before you read the document.

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
| `--all` | `-a` | `false` | Seed every enabled module. |

**Notes**

- This command is not confirmable. `db:seed` does its own production confirmation.
- Seeder lookup order for a module:
  1. Classes listed in `migration.seeds` of `module.json`.
  2. `<modules.namespace>\<Module>\<seeder namespace>\<Module>DatabaseSeeder` (where `<seeder namespace>` comes from `paths.generator.seeder`).
  3. The same class name under the last folder of each `scan.paths` entry.
- A missing class in `migration.seeds` prints a warning and is skipped. A module with no seeder prints a warning and succeeds.
- With `--class`, the command replaces the short class name of each seeder found with the given class name in the same namespace.
- A seeder that throws an exception is reported through the exception handler and counts as a failure.
- Each module starts with `Seeding <Name> Module ......`, followed by `db:seed` output and any warnings.
- When every seeder succeeds, the command prints `INFO  Module [Blog] seeded.`.
- On a `db:seed` failure, exception, or cancelled confirmation, the command prints `ERROR  Module [Blog] seeding failed.` and exits with `1`. Remaining modules still run.

```bash
php artisan module:seed Blog --class=PostSeeder
```
