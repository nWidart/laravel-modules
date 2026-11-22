---
title: Utility commands
description: Setup, Composer autoload, v5 status migration and PHPUnit coverage commands.
---

This page lists four commands that do not manage a single module's lifecycle, generate a file, or touch the database.

## Shared behaviour

`module:composer-update` extends `BaseCommand`, the package base class of the commands that run one action for each selected module. It inherits the behaviour in [Module management commands](/laravel-modules/reference/commands/module-management/#shared-behaviour): a variadic `module` argument, `--all` (`-a`), the interactive prompt, and exit status `1` when an action fails. `module:setup`, `module:v6:migrate` and `module:update-phpunit-coverage` do not extend it.

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

- The command looks for the key `<modules namespace>\<Module>\App\` or `<modules namespace>\<Module>\<app folder namespace>\` in `autoload.psr-4`. It replaces that key, in place, with `<modules namespace>\<Module>\`. The value (the folder) stays as it was, or becomes `paths.app_folder` when empty.
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

Adds the app folder of each enabled module to the `<source><include>` list of `phpunit.xml`, and removes the entries of modules that are not enabled.

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
- The command edits the file line by line and keeps its formatting. When it cannot remove or place an entry, it prints a warning. When there is no `<include>` element, it creates one that also holds a `<directory>./app</directory>` entry.
- Exit status: `0` on success; `100` when `phpunit.xml` does not exist; `99` when the statuses file does not exist; `98` when the statuses file is not valid JSON; `97` when `phpunit.xml` is not valid XML.
