---
title: Module management commands
description: Artisan commands that list, enable, disable, install, update and delete modules, and inspect their commands, language files and models.
---

This page lists the commands that manage existing modules. `module:make` and the `module:make-*` generators are in [Generator commands](/laravel-modules/reference/commands/generators/).

## Shared behaviour

`BaseCommand` is the base class of the commands that run one action for each selected module (see [Contracts and base classes](/laravel-modules/reference/api/contracts/#basecommand)). Nine commands on this page extend it and share the behaviour below: `module:enable`, `module:disable`, `module:delete`, `module:use`, `module:unuse`, `module:update`, `module:dump`, `module:lang` and `module:list-commands`. The other four (`module:list`, `module:install`, `module:prune` and `module:model-show`) define their own arguments and say so in their entries.

### Arguments and options of `BaseCommand` commands

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `module` (argument) | | none | One or more module names. Optional, variadic. |
| `--all` | `-a` | `false` | Run the action for every module. |

### Module selection

- With names, the command runs the action once per name, in the order given.
- With `--all`, the command replaces the names with the module list. A command with a `--direction` option uses the enabled modules ordered by priority. Any other command uses every module, enabled or disabled.
- With neither, an interactive terminal shows a multi-select prompt named `Select Modules`. Its first entry, `All`, selects every module.
- With neither and no interactive input (`--no-interaction`, or no TTY), the command prints `No module given. Pass a module name or --all.` and exits with status `1`.
- The command checks that every named module exists before it runs any action. An unknown name throws `ModuleNotFoundException` and nothing runs.

### Exit status

An action that fails does not stop the remaining modules. The command exits with `1` if the action failed for at least one module, and `0` otherwise.

### Confirmation

`module:delete` is the only confirmable command on this page: it has a `--force` option and asks for confirmation. See [ConfirmableCommand](/laravel-modules/reference/api/contracts/#confirmablecommand).

## module:list

Lists the modules with their status, priority and path.

```bash
php artisan module:list [--only=ONLY] [--direction=DIRECTION]
```

**Arguments**

This command has no arguments. It has no `--all` option.

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--only` | `-o` | none | Filter: `enabled`, `disabled` or `priority`. Any other value lists every module. |
| `--direction` | `-d` | `asc` | Sort direction. Read only with `--only=priority`. |

**Notes**

- Each row shows `[Enabled]` or `[Disabled]`, the module name, the module path and the priority in brackets.
- `--only=priority` lists the enabled modules only, ordered by the `priority` field of `module.json`.
- The command always exits with `0`.

```bash
php artisan module:list --only=priority --direction=desc
```

## module:enable

Marks the given modules as enabled.

```bash
php artisan module:enable [<module>...] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | `-a` | `false` | Enable every module. |

**Notes**

- Each module prints its previous status.
- The status is stored by the activator. The default `FileActivator` writes it to `modules_statuses.json` in the application root.
- The module fires the `enabling` and `enabled` [events](/laravel-modules/reference/api/events/).

```bash
php artisan module:enable Blog Shop
```

## module:disable

Marks the given modules as disabled.

```bash
php artisan module:disable [<module>...] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | `-a` | `false` | Disable every module. |

**Notes**

- Each module prints its previous status.
- The module fires the `disabling` and `disabled` [events](/laravel-modules/reference/api/events/).
- A module that has no entry in the statuses file counts as disabled.

```bash
php artisan module:disable Blog
```

## module:delete

Deletes the module directory and removes the module from the activator.

```bash
php artisan module:delete [<module>...] [-a|--all] [--force]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | `-a` | `false` | Delete every module. |
| `--force` | | `false` | Skip the confirmation. |

**Notes**

- The command is confirmable, and it asks for confirmation in every environment. See [ConfirmableCommand](/laravel-modules/reference/api/contracts/#confirmablecommand). `Module::prohibitDestructiveCommands()` prohibits it too.
- The module fires the `deleting` and `deleted` [events](/laravel-modules/reference/api/events/).
- After the action, the command clears the repository cache of scanned modules.

```bash
php artisan module:delete Blog --force
```

## module:use

Stores a module name as the module to use when a generator command gets no `module` argument.

```bash
php artisan module:use [<module>...] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | `-a` | `false` | Run the action for every module. |

**Notes**

- The repository keeps one used module. The name is written to `storage/app/modules/modules.used` and stays there until [`module:unuse`](#moduleunuse) deletes the file. Each module processed overwrites the previous one, so the last module given wins.
- The module fires the `used` [event](/laravel-modules/reference/api/events/).
- Generator commands read the stored name when their optional `module` argument is empty. See [Generator commands](/laravel-modules/reference/commands/generators/#shared-behaviour).

```bash
php artisan module:use Blog
```

## module:unuse

Clears the module stored by `module:use`.

```bash
php artisan module:unuse [<module>...] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | `-a` | `false` | Run the action for every module. |

**Notes**

- The command requires a module name or `--all`, like every `BaseCommand` command. It deletes the file written by `module:use` whatever the name is, as long as the module exists.

```bash
php artisan module:unuse Blog
```

## module:install

Installs a module from Composer or from a Git repository, then runs `module:update` for it.

```bash
php artisan module:install [<name> [<version>]] [--timeout=TIMEOUT] [--path=PATH] [--type=TYPE] [--tree] [--no-update]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | no | Package name in the form `vendor/name`. Without it, the command reads `modules.json`. |
| `version` | no | Composer version constraint, or the Git branch, tag or commit to check out. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--timeout` | | `3360` | Process timeout in seconds. |
| `--path` | | the module path from the repository | Install destination. |
| `--type` | | none | Install source. See the table below. |
| `--tree` | | `false` | Install as a Git subtree. |
| `--no-update` | | `false` | Skip the `module:update` call after the install. |

**Values of `--type`**

| Value | Source |
| --- | --- |
| none, or `composer` | Composer: `composer require <name>:<version>`. The default version is `dev-master`. |
| `github` | `git@github.com:<name>.git` |
| `github-https` | `https://github.com/<name>.git` |
| `gitlab` | `git@gitlab.com:<name>.git` |
| `bitbucket` | `git@bitbucket.org:<name>.git` |
| a URL | The URL, used as the repository URL. |
| `user@host` | `user@host:<name>.git` |

**Notes**

- Any `--type` other than `composer` installs with `git clone` into the destination, then checks out the version. The default version is `master`.
- The destination is `--path` if given, otherwise the repository path for the studly-case last segment of `name`.
- `--tree` applies to Git sources only. It runs `git remote add` (named after the module) and `git subtree add --squash`. The destination must be inside the project. If the remote already exists with another URL, the command fails.
- After a successful install, and unless `--no-update` is given, the command reloads the module list and runs `module:update <module>`. The module name comes from the `name` field of the cloned `module.json` for Git installs, or from the package name for Composer installs. If the repository cannot find the module, the command prints an error and exits with `1`.
- Without `name`, the command reads `modules.json` in the application root and installs each entry of its `require` list. See [modules.json](/laravel-modules/reference/module-json/#modulesjson). The command installs the remaining entries when one fails and exits with `1`. A missing file prints `File 'modules.json' does not exist in your project root.` and exits with `1`.
- The options act on every entry of `modules.json`: `--type` and `--tree` are the fallback for entries without a `type`, and `--path` and `--timeout` apply to all entries.
- The command passes the name, version, path and URL as process arguments, not through a shell.

```bash
php artisan module:install acme/blog 1.2.0 --type=github-https
```

## module:update

Installs the Composer dependencies a module declares.

```bash
php artisan module:update [<module>...] [-a|--all]
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

- The command reads the `require`, `require-dev` and `scripts` keys of the module `composer.json`. See [module composer.json](/laravel-modules/reference/module-json/#module-composerjson).
- It runs `composer require <package>:<constraint> ...` in the application root for `require`, and `composer require --dev ...` for `require-dev`. It does not run `composer update`. Composer runs with `--quiet` unless `composer.composer-output` is `true`.
- It merges the module `scripts` into the `scripts` of the application `composer.json`. A script key that exists in both files keeps both commands, without duplicates.

```bash
php artisan module:update Blog
```

## module:dump

Runs `composer dump -o -n -q` inside each module directory.

```bash
php artisan module:dump [<module>...] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | `-a` | `false` | Dump every module. |

**Notes**

- The working directory returns to the application root after each module.
- A module for which Composer exits with a non-zero status counts as failed.

```bash
php artisan module:dump --all
```

## module:list-commands

Lists the Artisan command classes found in each module.

```bash
php artisan module:list-commands [<module>...] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | `-a` | `false` | List the commands of every module. |

**Notes**

- The command scans these directories of the module, when they exist: `Commands`, `Console/Commands`, `<app folder>/Commands`, `<app folder>/Console` and `<app folder>/Console/Commands`. The app folder is `paths.app_folder`.
- It lists the PHP files whose class extends a Symfony or Illuminate command. For each class it shows the command name. When it cannot instantiate the class, it shows the class name, or `Unknown` if no name is available.
- The output groups the commands by their directory (`Commands` or `Console/Commands`).
- A module without commands prints `No commands found in module <name>`.

```bash
php artisan module:list-commands Blog
```

## module:lang

Compares the language directories of a module and reports missing files and keys.

```bash
php artisan module:lang [<module>...] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | `-a` | `false` | Check every module. |

**Notes**

- The command reads the sub-directories of the `lang` generator path of the module (`paths.generator.lang.path`, default `lang`). Each sub-directory is one locale.
- It reports a file that exists in one locale and not in another, and a key (in dot notation) that exists in one locale file and not in the same file of another locale. It reads PHP array files.
- A module with no locale directory prints `No language files found in module <name>`. A module with one locale directory prints `Only one language file found in module <name>`. Neither counts as a failure.
- A missing file or key marks the module as failed, so the command exits with `1`.

```bash
php artisan module:lang Blog
```

## module:prune

Prunes the prunable Eloquent models of the given modules. It wraps Laravel `model:prune`.

```bash
php artisan module:prune <module>... [--all] [--model=MODEL] [--except=EXCEPT] [--path=PATH] [--chunk=CHUNK] [--pretend]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | yes, unless `--all` is given | One or more module names. In an interactive terminal, a missing argument opens a multi-select prompt. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | | `false` | Select every enabled module. It replaces the `module` argument. |
| `--model` | | none | Class names of the models to prune. Repeatable. |
| `--except` | | none | Class names of the models to exclude. Repeatable. |
| `--path` | | none | Absolute directory paths where models are located. Repeatable. |
| `--chunk` | | `1000` | Number of models to retrieve per chunk. |
| `--pretend` | | `false` | Display the number of prunable records instead of deleting them. |

**Notes**

- This command does not extend `BaseCommand`. `--all` has no shortcut and selects the enabled modules only. A run without `module` and without `--all` fails on the missing argument, unless an interactive terminal answers the prompt.
- The command scans the `model` generator path of each module (`paths.generator.model.path`) for PHP files, and keeps the models that are prunable.
- `--model` and `--except` cannot be combined. The command throws `InvalidArgumentException`. With `--model`, the command prunes the listed classes that exist and ignores the module models.

```bash
php artisan module:prune Blog --pretend
```

## module:model-show

Shows information about an Eloquent model that lives in a module. It wraps Laravel `model:show`.

```bash
php artisan module:model-show <model> [--database=DATABASE] [--json]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `model` | yes, asked when missing | Model class name without namespace. In an interactive terminal, a missing argument opens a search prompt. A non-interactive run fails on the missing argument. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--database` | | none | Database connection to use. |
| `--json` | | `false` | Output the model as JSON. |

**Notes**

- The command looks for `<paths.modules>/*/<paths.generator.model.path>/<model>.php` and resolves the class name from the path. It does not read the module status.
- The command has no `module` argument. It searches every module directory.

```bash
php artisan module:model-show Post --json
```
