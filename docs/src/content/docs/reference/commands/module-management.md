---
title: Module management commands
description: Artisan commands that list, enable, disable, install, update and delete modules, and inspect their commands, language files and models.
---

This page lists the commands that manage existing modules. `module:make` and the `module:make-*` generators are in [Generator commands](/laravel-modules/reference/commands/generators/).

## Shared behaviour

`BaseCommand` runs one action per selected module (see [Contracts and base classes](/laravel-modules/reference/api/contracts/#basecommand)). Eight commands here extend it: `module:enable`, `module:disable`, `module:delete`, `module:use`, `module:update`, `module:dump`, `module:lang`, and `module:list-commands`. The other six (`module:list`, `module:unuse`, `module:install`, `module:surface`, `module:prune`, and `module:model-show`) define their own arguments.

### Arguments and options of `BaseCommand` commands

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `module` (argument) | | none | One or more module names. Optional, variadic. |
| `--all` | `-a` | `false` | Run the action for every module. |

### Module selection

- With names, the command runs the action once per name, in the order given.
- With `--all`, the command selects the module list automatically. Database commands select enabled modules ordered by priority (`--direction`, or ascending for `module:migrate-refresh`). See [Database commands](/laravel-modules/reference/commands/database/#shared-behaviour). Other commands select every module, enabled or disabled.
- With neither, an interactive terminal shows a `Select Modules` multi-select prompt listing `All` and the same module list as `--all`.
- A module given by name runs whatever its status.
- With neither and no interactive input (`--no-interaction`, or no TTY), the command prints `No module given. Pass a module name or --all.` and exits with `1`.
- The command validates every named module before running any action. Unknown names print `Module [Nope] does not exist.` (or `Modules [Nope, Typo] do not exist.`) and exit with `1`. No action runs, even for valid names.

### Exit status

An action that fails does not stop the remaining modules. The command exits with `1` if the action failed for at least one module, and `0` otherwise.

### Confirmation

`module:delete` is the only confirmable command on this page: it has a `--force` option and asks for confirmation. See [ConfirmableCommand](/laravel-modules/reference/api/contracts/#confirmablecommand).

## module:list

Lists the modules with their status, priority and path.

```bash
php artisan module:list [-o|--only=ONLY] [-d|--direction=DIRECTION]
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
- With `modules.skip_broken_modules` set to `true`, a skipped module shows `[Broken]`, its name and the reason instead of the path and priority. A module with a missing provider class is marked `[Broken]` in its own row, so it appears with `--only=enabled`. A module with an invalid `module.json` is an appended `[Broken] <label>` row, shown only without `--only`. The label is the folder name, or the directory relative to the base path when the folder name is already taken by a listed module. See [Skip broken modules](/laravel-modules/concepts/how-modules-boot/#skip-broken-modules).
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

- The command is confirmable, and it asks for confirmation in every environment. See [ConfirmableCommand](/laravel-modules/reference/api/contracts/#confirmablecommand). `Module::prohibitDestructiveCommands()` prohibits it too, and so does `ModuleDeleteCommand::prohibit()`, which prohibits only this command.
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

Forgets the module stored by `module:use`.

```bash
php artisan module:unuse [<module>...] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Ignored. The used module is forgotten, whatever the name. Kept for backward compatibility. An unknown name is accepted. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | `-a` | `false` | Ignored. Kept for backward compatibility. |

**Notes**

- The command takes no module name and never prompts. It deletes the file written by `module:use` and exits with status `0`.
- When a module is in use, it prints `Forget Using Blog Module ......... DONE`, with the name of that module, and the module fires the `unused` [event](/laravel-modules/reference/api/events/).
- When no module is in use, or the stored name is a module that no longer exists, it prints `No module is in use.` and fires no event. The stored file is deleted in both cases.

```bash
php artisan module:unuse
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
| `--path` | | the module path from the repository | Destination folder of one module. Not accepted when `modules.json` lists more than one module. |
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
| a URL with a scheme, such as `https://git.example.com/acme/blog.git` or `file:///srv/git/blog.git` | The URL as given, used as the repository URL. `<name>` is not appended. `file://` clones a local repository. |
| `user@host`, such as `git@git.example.com` | `user@host:<name>.git` |

Any other value—including a bare local path such as `/srv/git/blog.git`—fails with `Unable to resolve a git repository URL for module [<name>] with type [<type>].` Use `file:///srv/git/blog.git` for a local repository.

**Notes**

- Any `--type` other than `composer` clones with `git clone` into the destination, then checks out the version (default `master`).
- Destination is `--path` if given, otherwise the repository path for the studly-case last segment of `name`. Composer installs ignore it; `--tree` uses it as the subtree prefix.
- `--tree` applies to Git sources only. It runs `git remote add` (named after the module) and `git subtree add --squash`. The destination must be inside the project. An existing remote with another URL fails.
- After install (unless `--no-update` is passed), the command reloads modules and runs `module:update <module>`. The module name comes from `module.json` (`name`) for Git installs, or the package name for Composer installs. Missing modules print an error and exit with `1`.
- Without `name`, the command installs each `require` entry from `modules.json` in the application root. See [modules.json](/laravel-modules/reference/module-json/#modulesjson). A failing entry does not stop the rest, and the command exits with `1`. A missing file prints `File 'modules.json' does not exist in your project root.` and exits with `1`.
- `--type` is the fallback for entries without `type`. `--tree` and `--timeout` apply to every entry. `modules.json` entries have no `tree` key.
- `--path` sets the folder for a single module. When `modules.json` lists multiple entries, the command exits with `1` before installing and prints `The --path option sets the folder of a single module, but modules.json lists <count> modules. Remove --path and set a "path" on each entry of modules.json instead.`
- With one entry, the entry's `path` wins over `--path`. Without an entry `path`, the command uses `--path`, then the default folder.
- A relative `path` resolves from the application root. If `path` is outside the scanned folders, install succeeds but `module:update` fails because the repository cannot find the module. Pass `--no-update` to skip it.
- The command passes name, version, path, and URL as process arguments, not through a shell.

**Errors**

Each case exits with `1`.

| Case | Message |
| --- | --- |
| `require` is not a list | `The "require" key of modules.json must be a list of entries such as {"name": "vendor/module"}.` |
| An entry is not an object | `Skipping entry #<n> of the modules.json "require" list: expected an object such as {"name": "vendor/module"}, got <type>.` |
| `name` is missing or not a non-empty string | `Skipping entry #<n> of the modules.json "require" list: "name" must be a package name such as "vendor/module".` |
| `version` or `type` is set and not a scalar | `Skipping entry #<n> of the modules.json "require" list: "version" of [<name>] must be a string.` (the same form for `"type"`) |
| `--tree` and the project is not a Git repository | `Failed to install module [<name>]: A subtree install (--tree) needs the project [<base path>] to be a git repository: run git init and commit the project first.` |
| `--tree` and the repository has no commit | `Failed to install module [<name>]: A subtree install (--tree) needs a git repository with at least one commit: commit the project first.` |
| `--tree` and tracked files have staged or unstaged changes | `Failed to install module [<name>]: A subtree install (--tree) needs a clean working tree: commit or stash your changes first. Untracked files are fine.` |

- A bad `modules.json` entry is skipped and counts as a failure. The command installs the remaining entries.
- The `--tree` checks run before any `git` process. Untracked files are accepted. Installs without `--tree` are not checked.

```bash
php artisan module:install acme/blog 1.2.0 --type=github-https
```

Install every entry of `modules.json`, each into its own `path`:

```bash
php artisan module:install
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

- Scans these directories when present: `Commands`, `Console/Commands`, `<app folder>/Commands`, `<app folder>/Console`, and `<app folder>/Console/Commands`. The app folder comes from `paths.app_folder`.
- It lists the PHP files whose class extends a Symfony or Illuminate command. For each class it shows the command name. When it cannot instantiate the class, it shows the class name, or `Unknown` if no name is available.
- The output groups the commands by their directory (`Commands` or `Console/Commands`).
- A module without commands prints `No commands found in module <name>`.

```bash
php artisan module:list-commands Blog
```

## module:lang

Compares the language directories of a module and reports missing files and keys.

```bash
php artisan module:lang [<module>...] [-a|--all] [--json]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | `-a` | `false` | Check every module. |
| `--json` | | `false` | Print the result as JSON. See [JSON output](#modulelang-json-output). |

**Notes**

- The command reads the sub-directories of the `lang` generator path of the module (`paths.generator.lang.path`, default `lang`). Each sub-directory is one locale.
- Compares PHP array files across locales. Reports files missing from another locale and keys (in dot notation) missing from the same file in another locale.
- A module with no locale directory prints `No language files found in module <name>`. A module with one locale directory prints `Only one language file found in module <name>`. Neither counts as a failure.
- A missing file or key marks the module as failed, so the command exits with `1`.

```bash
php artisan module:lang Blog
```

### module:lang JSON output

With `--json`, standard output holds one JSON line and the command follows the shared [JSON output](/laravel-modules/reference/commands/#json-output) rules. The `Checking languages ...`, `No language files found` and `Only one language file found` lines go to standard error. The exit code is the same as without `--json`.

```bash
php artisan module:lang Blog --json
```

```json
{
  "modules": [
    {
      "name": "Blog",
      "checked": true,
      "locales": {
        "en": {
          "missing_files": [],
          "missing_keys": {}
        },
        "fr": {
          "missing_files": ["auth.php"],
          "missing_keys": {
            "messages.php": ["welcome.title", "welcome.body"]
          }
        }
      }
    }
  ]
}
```

| Field | Description |
| --- | --- |
| `modules` | One entry per selected module, in run order. |
| `modules[].name` | The module name. |
| `modules[].checked` | `false` when the module has fewer than two locale folders. Nothing is compared then. |
| `modules[].locales` | Every locale folder found, sorted by name. `{}` when the module has no `lang` folder. |
| `locales.<locale>.missing_files` | File names that another locale has and this locale lacks. |
| `locales.<locale>.missing_keys` | Per file, the dotted keys that another locale has in its copy of the file and this locale lacks in its copy. Only files this locale has. A missing file appears in `missing_files`. |

Empty lists and maps are `[]` and `{}`.

When no module is found, or no module is given, standard output is `{"modules":[]}` and the exit code is non-zero. Check the exit code before you read the result.

## module:surface

Shows, sets or clears the `surface` key of the `module.json` of a module: the classes, routes and console commands the module offers. The file format and match rules are in [`surface`](/laravel-modules/reference/module-json/#surface).

```bash
php artisan module:surface <module> [--set [--class=CLASS]... [--route=ROUTE]... [--command=COMMAND]... [--stdin]] [--clear] [--json]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | yes | The module name. The command takes one module and does not extend `BaseCommand`. |

**Options**

| Name | Default | Description |
| --- | --- | --- |
| `--set` | `false` | Replace the surface. Lists you do not give are stored empty, so `--set` alone stores three empty lists. |
| `--class` | none | A fully qualified class name. Repeatable. Needs `--set`. A leading backslash is removed. |
| `--route` | none | A route URI as `route:list` shows it. Repeatable. Needs `--set`. Leading and trailing slashes are removed, and `/` stays `/`. Whitespace is an error. |
| `--command` | none | A console command name. Repeatable. Needs `--set`. |
| `--stdin` | `false` | With `--set`, read the surface from standard input as one JSON object. Cannot be combined with `--class`, `--route` or `--command`. |
| `--clear` | `false` | Remove the `surface` key. Cannot be combined with `--set`. |
| `--json` | `false` | Print the result as JSON. See [JSON output](#modulesurface-json-output). |

Without `--set` and `--clear`, the command shows the stored surface and writes nothing. It checks each stored entry with the `--set` rules, and the entry must already be in the form `--set` stores: a class with a leading backslash or a route with a trailing slash is invalid. An invalid entry exits with code `1`. Valid entries are shown as stored, with their spelling, order and duplicates.

**Notes**

- Every entry is checked before anything is written. One invalid entry writes nothing.
- The lists are deduplicated and sorted as described in [`surface`](/laravel-modules/reference/module-json/#surface).
- The standard input object has the keys `classes`, `routes` and `commands`. A missing key is an empty list. An unknown key, a value that is not an array, or an entry that is not a string is an error. A leading byte order mark is ignored.
- Use `--stdin` for long lists. `cmd.exe` limits a command line to about 8,000 characters.
- The command edits only the `surface` member of `module.json`. It replaces the value in place, appends the member after the last one, or removes it with its comma. Every other byte of the file stays the same, including numbers PHP cannot hold exactly, escapes such as `\u00e9`, and the layout. The surface uses the indentation of the file (4 spaces when there is none), does not escape slashes, and gets CRLF line endings in a CRLF file.
- Setting the surface that is already stored, or clearing when none is stored, does not touch the file. The result reports `changed` as `false`.

**Exit codes**

| Code | Meaning |
| --- | --- |
| `0` | Success. |
| `1` | Unknown module, or `module.json` cannot be read, is not a JSON object, holds an invalid `surface`, changed while the command ran, or cannot be written. |
| `2` | Usage or validation error: a bad option combination, or an invalid entry or standard input. Nothing is written. |

```bash
# Show the surface
php artisan module:surface Blog

# Set it
php artisan module:surface Blog --set \
    --class='Modules\Blog\Models\Post' \
    --route='api/posts/{post}' \
    --command='blog:publish'

# Declare that the module offers nothing
php artisan module:surface Blog --set

# Remove the surface
php artisan module:surface Blog --clear
```

Read the surface from standard input in bash:

```bash
cat <<'JSON' | php artisan module:surface Blog --set --stdin
{
    "classes": ["Modules\\Blog\\Models\\Post"],
    "routes": ["api/posts", "api/posts/{post}"],
    "commands": ["blog:publish"]
}
JSON
```

The same in PowerShell:

```powershell
@'
{
    "classes": ["Modules\\Blog\\Models\\Post"],
    "routes": ["api/posts", "api/posts/{post}"],
    "commands": ["blog:publish"]
}
'@ | php artisan module:surface Blog --set --stdin
```

### module:surface JSON output

With `--json`, standard output holds one JSON line and the command follows the shared [JSON output](/laravel-modules/reference/commands/#json-output) rules. The exit code is the same as without `--json`.

```bash
php artisan module:surface Blog --json
```

Success:

```json
{
  "module": "Blog",
  "path": "/var/www/Modules/Blog/module.json",
  "surface": {
    "classes": ["Modules\\Blog\\Models\\Post"],
    "routes": ["api/posts", "api/posts/{post}"],
    "commands": ["blog:publish"]
  },
  "changed": false
}
```

| Field | Description |
| --- | --- |
| `module` | The module name as stored in `module.json`. |
| `path` | The path of the `module.json` file. |
| `surface` | The stored surface after the command, with the keys `classes`, `routes` and `commands`. `null` when the module has no surface: for show without a stored surface, and after `--clear`. |
| `changed` | `true` when the command wrote the file. Always `false` when showing. |

Failure:

```json
{
  "module": "Blog",
  "errors": [
    {
      "list": "routes",
      "value": "api/my posts",
      "message": "The route \"api/my posts\" contains whitespace."
    }
  ]
}
```

| Field | Description |
| --- | --- |
| `module` | The module argument as given. |
| `errors` | One entry per problem, in the order found. |
| `errors[].list` | `classes`, `routes` or `commands` when the problem belongs to a list, else `null`. |
| `errors[].value` | The offending entry, or the unknown key for an unknown key in standard input, else `null`. |
| `errors[].message` | A human-readable message. |

## module:prune

Prunes the prunable Eloquent models of the given modules. It wraps Laravel `model:prune`.

```bash
php artisan module:prune [<module>...] [--all] [--model=MODEL] [--except=EXCEPT] [--chunk=CHUNK] [--pretend]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no, but the command needs a module or `--all` | One or more module names. In an interactive terminal, a missing argument opens a multi-select prompt. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | | `false` | Select every enabled module. It replaces the `module` argument. |
| `--model` | | none | Class names of the models to prune, or their names in the model folder of the selected modules. Repeatable. See the notes. |
| `--except` | | none | Class names of the models to exclude from pruning, or their names in the model folder of the selected modules. Repeatable. See the notes. |
| `--chunk` | | `1000` | Number of models to retrieve per chunk. |
| `--pretend` | | `false` | Display the number of prunable records instead of deleting them. |

**Notes**

- This command does not extend `BaseCommand`. It shares the module checks through the `ValidatesModuleArgument` trait. `--all` has no shortcut and selects the enabled modules only. A module given by name is pruned whatever its status. The module name `All` stands for `--all`.
- With neither a module nor `--all` in an interactive terminal, the `Select Modules` prompt lists `All` and the enabled modules. Without interactive input (`--no-interaction`, or no TTY), the command prints `No module given. Pass a module name or --all.` and exits with `1`.
- Validates every named module first. Unknown names print `Module [Nope] does not exist.` (or `Modules [Nope, Typo] do not exist.`) and exit with `1`. Nothing is pruned, even for valid names or when `--model` is given.
- Scans `paths.generator.model.path` in each module for prunable models. Prints `No prunable models found.` and exits with `0` when none match.
- `--model` and `--except` resolve each value in order:
  - First, as a name relative to a selected module's model folder (`Post`, `Archive/Old`, `Archive\Old`). Searches only the selected modules (`module` argument, or enabled modules with `--all`).
  - Next, as an existing fully qualified class name, even outside the selected modules.
  - A module model wins over a global class or facade alias (`--except=Log` excludes `Modules\Blog\Models\Log`, not the `Log` facade).
  - Every matching module model is included (`module:prune Blog Shop --model=Post` matches both `Blog` and `Shop`). There is no prompt.
  - `Post` does not match nested models like `Archive\Post`; pass `Archive/Post`.
  - Unmatched values fail both `--model` and `--except`. Prints `Model [Nope] does not exist in the selected modules.` (or `Models [Nope, Typo] do not exist in the selected modules.`), exits with `1`, and prunes nothing.
- `--model` and `--except` cannot be combined. The command throws `InvalidArgumentException`.

```bash
php artisan module:prune Blog --pretend
php artisan module:prune Blog Shop --model=Post
```

## module:model-show

Shows information about an Eloquent model that lives in a module. It wraps Laravel `model:show`.

```bash
php artisan module:model-show <model> [--database=DATABASE] [--json]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `model` | yes, asked when missing | Model name, or fully qualified class name. See the notes for the accepted forms. In an interactive terminal, a missing argument opens a search prompt. A non-interactive run fails on the missing argument. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--database` | | none | Database connection to use. |
| `--json` | | `false` | Output the model as JSON. |

**Notes**

- A name that Laravel resolves to an existing class is used unchanged: a fully qualified class name, or a model in the application `App\Models` namespace. An application model wins over a module model with the same short name.
- Otherwise the command looks for `<paths.modules>/*/<paths.generator.model.path>/<name>.php` in every module and shows the matching class. It does not read the module status, so a model of a disabled module matches too.
- Accepted forms: `Post`, a sub-path inside the models folder (`Admin/Post` or `Admin\Post`, optional leading separator), or a fully qualified class name. `Models/Post` is not supported. Names containing `*`, `?`, or `[` are skipped.
- When multiple modules contain the same model name, interactive runs prompt for a candidate class. Non-interactive runs exit with `1` and print `Model [Tag] is ambiguous, it exists in several modules: Modules\Shop\Models\Tag, Modules\Webapp\Models\Tag. Pass the fully qualified class name.`
- When nothing matches, the command fails with Laravel's error, `Target class [App\Models\<name>] does not exist.`
- The command has no `module` argument. It searches every module directory.

```bash
php artisan module:model-show Post --json
php artisan module:model-show 'Modules\Shop\Models\Tag'
```
