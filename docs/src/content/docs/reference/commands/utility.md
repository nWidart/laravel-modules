---
title: Utility commands
description: Setup, Composer autoload, v5 status migration, PHPUnit coverage and module test commands.
---

This page lists five commands that do not manage a single module's lifecycle, generate a file, or touch the database.

## Shared behaviour

`module:composer-update` and `module:test` extend `BaseCommand`. It inherits the behaviour in [Module management commands](/laravel-modules/reference/commands/module-management/#shared-behaviour): variadic `module`, `--all` (`-a`), the interactive prompt, and exit status `1` on failure. `module:setup`, `module:v6:migrate`, and `module:update-phpunit-coverage` do not extend `BaseCommand`.

Each entry lists its arguments and its options. A command without arguments and options has no separate example: its synopsis is the example.

## module:setup

Creates the modules directory and the public assets directory when they do not exist.

```bash
php artisan module:setup
```

**Arguments**

This command has no arguments.

**Options**

This command has no options.

**Notes**

- The command creates `paths.modules` (default `Modules`) and `paths.assets` (default `public/modules`), with the permissions `0755`.
- A directory that exists prints `Modules directory already exists` or `Assets directory already exists`. The command exits with `0` in both cases.

## module:composer-update

Rewrites the legacy `autoload.psr-4` entry of a module `composer.json` so that the module namespace maps to the app folder.

```bash
php artisan module:composer-update [<module>...] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | `-a` | `false` | Update every module. |

**Notes**

- Looks for `<modules namespace>\<Module>\App\` or `<modules namespace>\<Module>\<app folder namespace>\` in `autoload.psr-4`. Replaces the key in place with `<modules namespace>\<Module>\`. Keeps the folder value, or sets `paths.app_folder` when empty.
- A `composer.json` without such a key stays unchanged. The command does not run Composer. The new mapping takes effect when Composer regenerates the autoloader, which [`module:dump`](/laravel-modules/reference/commands/module-management/#moduledump) and `composer dump-autoload` do.
- The file keeps its key order, its empty objects and its trailing newline.

```bash
php artisan module:composer-update Blog
```

## module:v6:migrate

Copies the `active` field of each `module.json` (the version 5 way to store the status) into the activator.

```bash
php artisan module:v6:migrate
```

**Arguments**

This command has no arguments.

**Options**

This command has no options.

**Notes**

- For each module with `"active": 1` the command enables the module. For `"active": 0` it disables the module. A module without `active`, or with another value, is left alone.
- The command prints `All modules have been migrated.` and a table of the modules it changed. It always exits with `0`.
- The command does not remove the `active` field.

## module:update-phpunit-coverage

Syncs `<source><include>` in `phpunit.xml` with the app folders of enabled modules, removing entries for modules that are not enabled.

```bash
php artisan module:update-phpunit-coverage
```

**Arguments**

This command has no arguments.

**Options**

This command has no options.

**Notes**

- The command reads `phpunit.xml` in the application root and the statuses file `modules.activators.file.statuses-file` (default `modules_statuses.json`).
- For each module with status `true` whose `<paths.modules>/<Module>/<paths.app_folder>` directory exists, it ensures a `<directory suffix=".php">` entry in `<source><include>`.
- It removes the entries that point into `<paths.modules>/<Module>/<paths.app_folder>` for a module that is no longer wanted.
- The command edits the file line by line and keeps its formatting, indentation and line endings (LF or CRLF).
- New entries go where the file allows:
  - In an existing multi-line `<include>` block, before `</include>`.
  - In an empty `<include></include>` or `<include/>` on one line inside `<source>`: the command expands it with the module entries only.
  - In `<source>` with no `<include>`: inserts an `<include>` block containing `<directory>./app</directory>` and each enabled module. Supports multi-line `<source>`, empty `<source></source>`, self-closing `<source/>` or `<source />` (keeping attributes), and `<source>` with trailing elements on the same line (moved to a new line).
  - `<source><include>` on one line with the rest on later lines: before `</include>`.
  - With no `<source>` element, adds a `<source>` block before `</phpunit>`.
- `<directory>./app</directory>` is the application's `app` folder, not `paths.app_folder`. It is written only when creating a new `<include>` block.
- Unsupported layouts: the whole block on one line (`<source><include><directory>...</directory></include></source>`), `...</directory></include>` on one line, or `<source/>` with trailing text on the same line. The file stays unchanged, the command prints `Could not find where to add the module entries in phpunit.xml: add them by hand.`, and omits the success line.
- When it cannot remove an entry, it prints `Could not remove the entry [<path>] from phpunit.xml: remove it by hand.`
- The command prints `phpunit.xml updated with enabled module directories.` only when it could place the entries. After a warning it still exits with `0`, so scripts that run it after each module change do not fail.

**Exit status**

| Status | Meaning |
| --- | --- |
| `0` | Done, also after the warnings above. |
| `100` | `phpunit.xml` does not exist. |
| `99` | The statuses file does not exist. |
| `98` | The command cannot decode the statuses file. Prints `Error decoding JSON from <path>: <error>`. See below. |
| `97` | `phpunit.xml` is empty or not valid XML. |

Status `98` happens only when decoding the statuses file fails, in two cases:

- The file is empty or holds only whitespace. `FileActivator` treats that as no statuses and boots, but the command's JSON decode fails.
- The file holds invalid JSON and the active activator is not `FileActivator`. The command still reads the file named by `modules.activators.file.statuses-file`, and nothing else reads it at boot.

With a custom activator and a valid statuses file, the command exits with `0`. With `FileActivator` and non-empty invalid JSON, the command never runs: `FileActivator` throws [`InvalidJsonException`](/laravel-modules/reference/api/exceptions/#invalidjsonexception) during boot and `artisan` exits with `1`.

## module:test

Runs the tests in the `tests` folder of each given module.

```bash
php artisan module:test [<module>...] [-a|--all] [--filter=<pattern>] [--parallel] [--json]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | `-a` | `false` | Run the tests of every module, enabled or disabled. |
| `--filter` |  |  | Run only the tests that match the pattern. Passed to the runner. |
| `--parallel` |  | `false` | Run the tests in parallel. Passed to the runner. Needs `artisan test` or Pest. |
| `--json` |  | `false` | Print the result as one JSON document. See [JSON output](/laravel-modules/reference/commands/#json-output). |

**Notes**

- The command runs from the application root, with no timeout, and streams the runner output.
- The runner is `php artisan test <folder>` when the application has the `test` command (a default Laravel application does, through `nunomaduro/collision`). Otherwise it is `vendor/bin/pest <folder>`, then `vendor/bin/phpunit <folder>`.
- `--parallel` with plain PHPUnit prints `--parallel needs artisan test or Pest.` and exits with `1`.
- With Pest or PHPUnit as the runner, the command unsets the variables of the application's `.env` file for the child process, as `artisan test` does, so the values in `phpunit.xml` apply. Pass `--env` to keep them.
- A module without a `tests` folder prints `Module [<Module>] has no tests folder.`, is skipped, and does not change the exit status.

**Exit status**

The exit status of the test run. With several modules, the first non-zero status of a module, else `0`.

**JSON output**

With `--json`, the runner output goes to standard error. Standard output holds one JSON line at the end:

```json
{"modules":[{"name":"Blog","ran":true,"passed":4,"failed":3,"skipped":3,"exit_code":1},{"name":"Shop","ran":false,"passed":null,"failed":null,"skipped":null,"exit_code":null}],"passed":4,"failed":3,"skipped":3,"exit_code":1}
```

| Key | Description |
| --- | --- |
| `modules[].name` | The module name. |
| `modules[].ran` | `false` when the module has no `tests` folder. |
| `modules[].passed` | Tests that passed: tests minus failures, errors and skipped. |
| `modules[].failed` | Failures plus errors. |
| `modules[].skipped` | Skipped tests. |
| `modules[].exit_code` | The exit status of the runner for this module. |
| `passed`, `failed`, `skipped` | The sums over the modules that have counts. |
| `exit_code` | The exit status of the command. |

- The counts come from a JUnit report. For a module with `ran: false`, `passed`, `failed`, `skipped` and `exit_code` are `null`.
- The counts are also `null` when the runner wrote no report, for example after a crash.

```bash
php artisan module:test Blog --filter=PostIndexTest
php artisan module:test --all --json 2>/dev/null | jq '.failed'
```
