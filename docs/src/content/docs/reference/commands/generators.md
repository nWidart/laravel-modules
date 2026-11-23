---
title: Generator commands
description: Artisan commands that create modules and generate classes and files inside a module.
---

This page lists `module:make`, the 41 `module:make-*` generators and `module:route-provider`. It also documents `module:generators`, which lists them. Commands that manage existing modules are in [Module management commands](/laravel-modules/reference/commands/module-management/).

## Shared behaviour

Except for `module:make` (which creates a whole module), every command on this page generates a file inside a module. Two commands generate multiple files:

- `module:make-model` runs related generators for its flags (`--controller`, `--factory`, `--migration`, `--request`, `--resource`, `--policy`, `--seed`).
- `module:make-component` also runs `module:make-component-view`.

### Arguments

- `name` is the first argument. `module:make-controller` names it `controller` and `module:make-model` names it `model`. `module:make-event-provider` and `module:route-provider` take no name argument.
- `module` is optional on every generator except `module:make-class`, where it is required. When omitted, the command uses the module stored by [`module:use`](/laravel-modules/reference/commands/module-management/#moduleuse). If none is stored or the stored module is missing, the command prints `No module given, and no module is set with module:use. Pass the module name, or run php artisan module:use <module> first.`, writes nothing, and exits with `1`.
- An unknown module name prints `Module [Nope] does not exist.`, writes nothing, and exits with `1`.
- Generators do not check whether the module is enabled.

### Name handling

- Class names are converted to studly case. A `name` with `/` or `\` places the file in a sub-directory and matching sub-namespace (`Admin/Report` creates `Report` under `Admin`).
- An empty name, an absolute path, or a `..` segment fails with exit status `1`. Segments that reduce to `..` after removing whitespace, hyphens, and underscores (such as `. .`) are also rejected.
- Missing directories are created automatically.

### Path and namespace

- Target folder: `<module path>/<paths.generator.<key>.path>`. Each command's `<key>` is in its notes. See [Configuration](/laravel-modules/reference/configuration/#pathsgenerator).
- Namespace: `<namespace>\<Module>\<generator namespace>`. The generator namespace uses `paths.generator.<key>.namespace` when set, else the generator path without `paths.app_folder`. Two exceptions apply:
  - `module:make-observer` ignores `paths.generator.observer.namespace` and always derives its namespace from `paths.generator.observer.path`.
  - `module:make-scope` derives its namespace from the last segment of `paths.generator.model.path` plus `\Scopes`.
- The `generate` flag of a generator key affects only `module:make`, not individual generator commands.

### Existing files

- An existing file stops the command with `File : <path> already exists.` and exit status `1`.
- Pass `--force` to overwrite an existing file. Every generator accepts `-f|--force`, except `module:make-model` (`-f` is `--factory`), `module:route-provider`, and `module:make`, which define `--force` without a shortcut. Per-command tables omit `--force` unless it differs.

### Dry run

Every generator and `module:make` accept `--dry-run` and `--json`. Per-command tables omit both, except in `module:make`.

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--dry-run` |  | `false` | List the files the command would write, and write nothing. |
| `--json` |  | `false` | Print the result as one JSON document. Needs `--dry-run`. |

```bash
php artisan module:make-model Post Blog --all --dry-run
```

`--dry-run` lists every file the command would write, in write order. The list includes the files of the generators the command calls: the sub-generators of `module:make`, the related generators of `module:make-model --all`, and the view of `module:make-component`. The command writes nothing: no files, folders, `.gitkeep`, `module.json` or status file, no `composer dump-autoload`, and no deletion with `--force`. The exit status is the one the real run would return.

The text output starts with `Dry run: these files would be written. Nothing was written.`, then one line per file, then the errors:

```text
  create  /app/Modules/Blog/app/Models/Post.php
  keep  /app/Modules/Blog/app/Http/Requests/PostRequest.php (exists)
```

With `--dry-run --json`, the command follows the [JSON output](/laravel-modules/reference/commands/#json-output) rules and prints one object:

| Field | Type | Description |
| --- | --- | --- |
| `command` | string | The command name, such as `module:make-model`. |
| `dry_run` | boolean | Always `true`. |
| `files` | array | The files the command would write, in write order. |
| `errors` | array | The error messages the real run would print. Empty when the run would succeed. |

Each item of `files`:

| Field | Type | Description |
| --- | --- | --- |
| `path` | string | Absolute path, with forward slashes. |
| `exists` | boolean | `true` when the file exists on disk now. |
| `action` | string | What the real run does with the file. See below. |

| `action` | Meaning |
| --- | --- |
| `create` | The file does not exist. |
| `replace` | The file exists and `--force` is given. |
| `keep` | The file exists and `--force` is not given. The real run stops with `File : <path> already exists.` |
| `delete` | The module folder that `module:make --force` deletes first. The files of the new module follow with `create`, and can show `exists: true`. |

```json
{"command":"module:make-model","dry_run":true,"files":[{"path":"/app/Modules/Blog/app/Models/Post.php","exists":false,"action":"create"},{"path":"/app/Modules/Blog/app/Http/Requests/PostRequest.php","exists":true,"action":"keep"}],"errors":["File : /app/Modules/Blog/app/Http/Requests/PostRequest.php already exists."]}
```

An error that stops the run leaves `files` empty:

```json
{"command":"module:make-model","dry_run":true,"files":[],"errors":["Module [Missing] does not exist."]}
```

- `--json` alone fails with `The --json option needs --dry-run.` and exit status `1`.
- A dry run does not report warnings, such as an unreplaced stub token.
- A dry run cannot predict a `composer dump-autoload` failure.
- A custom module repository that does not extend `FileRepository` cannot resolve a module that does not exist yet, so `module:make --dry-run` fails for it.

### Exit status

`0` on success. `1` on an invalid name, an unknown or missing module, an existing file or any other error.

## module:make

Creates one or more modules: the folder structure, `module.json`, `composer.json` and the starting classes.

```bash
php artisan module:make <name>... [-p|--plain] [--api] [--web] [--inertia] [-d|--disabled] [--force] [--dry-run] [--json] [--author-name=AUTHOR-NAME] [--author-email=AUTHOR-EMAIL] [--author-vendor=AUTHOR-VENDOR]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes, asked when missing | One or more module names. Repeatable. In an interactive terminal, a missing argument opens the prompt `What is the name of the module?`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--plain` | `-p` | `false` | Generate a plain module: folders and `module.json` only. |
| `--api` |  | `false` | Generate an API module (`module:make-controller --api` using `controller-api.stub`). All other `stubs.files` (views, routes, assets) are still written. |
| `--web` |  | `false` | Generate a web module (default). Wins over `--plain` and `--api`. |
| `--inertia` |  | `false` | Generate an Inertia module: an Inertia controller and four Inertia pages, no Blade views. |
| `--disabled` | `-d` | `false` | Create the module in the disabled state. |
| `--force` |  | `false` | Recreate the module when it already exists. |
| `--dry-run` |  | `false` | List the files the command would write, and write nothing. See [Dry run](#dry-run). |
| `--json` |  | `false` | Print the dry run as one JSON document. Needs `--dry-run`. |
| `--author-name` |  | `composer.author.name` | Author name written to `composer.json`. |
| `--author-email` |  | `composer.author.email` | Author email written to `composer.json`. |
| `--author-vendor` |  | `composer.vendor` | Vendor part of the package name in `composer.json`. |

**Notes**

- This command is not a generator in the sense of the shared behaviour above: it has no `module` argument and does not read `module:use`. Without `name`, an interactive terminal asks `What is the name of the module?`.
- The name is converted to studly case. It becomes the module folder and namespace. The converted name must match `^[A-Za-z_][A-Za-z0-9_]*$`. A name that does not match prints an error, the command goes on with the next name, and the exit status is `1`.
- Module type precedence (first match wins):
  1. `--web` -> `web`
  2. `--plain` with `--api` -> `web`
  3. `--plain` -> `plain`
  4. `--api` -> `api`
  5. Default -> `web`
  `--inertia` has no effect on a `plain` module.
- An existing module stops with `Module [<name>] already exists!` unless `--force` is given. With `--force`, the command deletes the existing module and creates it again.
- Before writing anything, the command renders every stub. A JSON stub that renders invalid JSON stops the command and leaves any existing module intact.
- If `composer.json` uses the app folder, every class generator path must sit inside `paths.app_folder` (or match its namespace when `paths.app_folder` is empty). Otherwise the command prints the offending keys and stops.
- Execution order:
  1. Create every `paths.generator` folder with `generate` set to `true` (adding `.gitkeep` when `stubs.gitkeep` is `true`).
  2. Write `module.json`.
  3. Unless `plain`, write `stubs.files` (see [Configuration](/laravel-modules/reference/configuration/#stubs)) and run the resource generators below.
  4. Set the module status in the activator.
- Resource generators (skipped for `plain` modules):
  - `module:make-seed <Name> --master` (`<Name>DatabaseSeeder`) when `seeder.generate` is `true`.
  - `module:make-provider <Name>ServiceProvider --master` when `provider.generate` is `true`; otherwise removes the `providers` entry from `module.json`.
  - `module:make-event-provider` and `module:route-provider` when their `generate` flag is `true` (a missing `event-provider` key follows `provider`).
  - `module:make-controller <Name>Controller` when `controller.generate` is `true` (with `--api` for API modules, `--inertia` for Inertia modules).
  - `module:make-inertia-page` for `Index`, `Create`, `Show`, and `Edit` when `--inertia` is passed.
- A `plain` module also loses the default service provider from the `providers` list of `module.json`.
- The module fires the `creating` and `created` [events](/laravel-modules/reference/api/events/).
- At the end, the command runs `composer dump-autoload` in the application root. If Composer fails, the command prints the error and exits with `1`. The command exits with `1` too when no module was created.
- A stub that still holds an unreplaced `$TOKEN$` after rendering prints a warning.

```bash
php artisan module:make Blog Shop --api
# Modules/Blog/ and Modules/Shop/
```

## module:make-action

Creates an action class in a module.

```bash
php artisan module:make-action <name> [<module>] [-i|--invokable]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Action class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--invokable` | `-i` | `false` | Generate a class with a single `__invoke()` method. |

**Notes**

- Writes `<Name>.php` to the `actions` generator path (`paths.generator.actions.path`, default `app/Actions`).
- Stub: `action.stub`, or `action-invoke.stub` with `--invokable`.

```bash
php artisan module:make-action CreatePost Blog
# Modules/Blog/app/Actions/CreatePost.php
```

## module:make-cast

Creates an Eloquent cast class in a module.

```bash
php artisan module:make-cast <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Cast class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>.php` to the `casts` generator path (`paths.generator.casts.path`, default `app/Casts`).
- Stub: `cast.stub`.

```bash
php artisan module:make-cast Money Blog
# Modules/Blog/app/Casts/Money.php
```

## module:make-channel

Creates a broadcast channel class in a module.

```bash
php artisan module:make-channel <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Channel class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>.php` to the `channels` generator path (`paths.generator.channels.path`, default `app/Broadcasting`).
- Stub: `channel.stub`.

```bash
php artisan module:make-channel OrderChannel Blog
# Modules/Blog/app/Broadcasting/OrderChannel.php
```

## module:make-class

Creates a class of any type in a module. The type decides the folder.

```bash
php artisan module:make-class <name> <module> [-t|--type=TYPE] [-s|--suffix] [-i|--invokable]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Class name. |
| `module` | yes | Module name. Required: this command does not fall back to `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--type` | `-t` | `class` | Type of the class, for example `class`, `service`, `repository`, `contract`. |
| `--suffix` | `-s` | `false` | Append the type to the class name. See the notes. |
| `--invokable` | `-i` | `false` | Generate a class with a single `__invoke()` method. |

**Notes**

- Stub: `class.stub`, or `class-invoke.stub` with `--invokable`. The type is singularized.
- For type `class`, the file goes to `paths.generator.class.path` (default `app/Classes`). For other types, the trailing `Classes` segment is replaced with the plural studly-case type (e.g. `app/Services` for `--type=service`). The `services` generator key is not read for the path.
- Namespace lookup order: `paths.generator.<plural type>.namespace`, `paths.generator.<type>.namespace`, then the computed path's namespace.
- `--suffix` strips any trailing singular or plural type from `name`, then appends the studly-case type (`Mailer` and `MailerService` with `--type=service --suffix` both become `MailerService`).

```bash
php artisan module:make-class Mailer Blog --type=service --suffix
# Modules/Blog/app/Services/MailerService.php
```

## module:make-command

Creates an Artisan command class in a module.

```bash
php artisan module:make-command <name> [<module>] [--command=COMMAND]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Command class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--command` |  | `command:name` | Terminal command (signature) assigned to the class. |

**Notes**

- Writes `<Name>.php` to the `command` generator path (`paths.generator.command.path`, default `app/Console`).
- Stub: `command.stub`.

```bash
php artisan module:make-command SendDigest Blog
# Modules/Blog/app/Console/SendDigest.php
```

## module:make-component

Creates a Blade component class and its view in a module.

```bash
php artisan module:make-component <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Component class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>.php` to the `component-class` generator path (`paths.generator.component-class.path`, default `app/View/Components`).
- Stub: `component-class.stub`. The command then runs `module:make-component-view` with the same `name`, `module` and `--force`.
- The component name in the class is `components.<name in lower case>`.

```bash
php artisan module:make-component Alert Blog
# Modules/Blog/app/View/Components/Alert.php
```

## module:make-component-view

Creates a Blade component view in a module.

```bash
php artisan module:make-component-view <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Component name. The file name is the name in lower case. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<name in lower case>.blade.php` to the `component-view` generator path (`paths.generator.component-view.path`, default `resources/views/components`).
- Stub: `component-view.stub`.
- The Blade comment in the view holds a random inspiring quote as plain text, like the one `module:make-view` writes.

```bash
php artisan module:make-component-view Alert Blog
# Modules/Blog/resources/views/components/alert.blade.php
```

## module:make-config

Creates a configuration file in a module.

```bash
php artisan module:make-config <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Config file name. The command adds `.php` when the name has no such ending, and keeps the case. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<name>.php` to the `config` generator path (`paths.generator.config.path`, default `config`).
- Stub: `config.stub`.

```bash
php artisan module:make-config settings Blog
# Modules/Blog/config/settings.php
```

## module:make-controller

Creates a controller in a module.

```bash
php artisan module:make-controller <controller> [<module>] [-p|--plain] [--api] [-i|--invokable] [--inertia]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `controller` | yes | Controller class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--plain` | `-p` | `false` | Generate a controller without methods. |
| `--api` |  | `false` | Generate a resource controller without the `create` and `edit` methods. |
| `--invokable` | `-i` | `false` | Generate a controller with a single `__invoke()` method. |
| `--inertia` |  | `false` | Generate an Inertia controller. |

**Notes**

- Writes `<Name>Controller.php` to the `controller` generator path (`paths.generator.controller.path`, default `app/Http/Controllers`). The command appends `Controller` unless the name already contains `controller`, in any case.
- Stub precedence (first match wins): `--plain` (`controller-plain.stub`), `--api` (`controller-api.stub`), `--invokable` (`controller.invokable.stub`), `--inertia` (`controller-inertia.stub`), default (`controller.stub`).

```bash
php artisan module:make-controller PostController Blog --api
# Modules/Blog/app/Http/Controllers/PostController.php
```

## module:make-enum

Creates an enum in a module.

```bash
php artisan module:make-enum <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Enum name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>.php` to the `enums` generator path (`paths.generator.enums.path`, default `app/Enums`).
- Stub: `enum.stub`.

```bash
php artisan module:make-enum PostStatus Blog
# Modules/Blog/app/Enums/PostStatus.php
```

## module:make-event

Creates an event class in a module.

```bash
php artisan module:make-event <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Event class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>.php` to the `event` generator path (`paths.generator.event.path`, default `app/Events`).
- Stub: `event.stub`.

```bash
php artisan module:make-event PostPublished Blog
# Modules/Blog/app/Events/PostPublished.php
```

## module:make-event-provider

Creates the `EventServiceProvider` of a module.

```bash
php artisan module:make-event-provider [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `EventServiceProvider.php` to the `provider` generator path (`paths.generator.provider.path`, default `app/Providers`).
- Stub: `event-provider.stub`.
- The command has no `name` argument. The `event-provider` generator key is not in `config/config.php`. `module:make` treats a missing key as following the `provider` entry.
- The generated provider registers only the listeners in its `$listen` array. It never discovers listeners. See [Listener discovery](/laravel-modules/reference/commands/generators/#listener-discovery).
- Overrides `configureEmailVerification()` with an empty body. Without this override, each module provider would register `SendEmailVerificationNotification` on `Registered` again, sending duplicate verification emails.

```bash
php artisan module:make-event-provider Blog
# Modules/Blog/app/Providers/EventServiceProvider.php
```

## module:make-exception

Creates an exception class in a module.

```bash
php artisan module:make-exception <name> [<module>] [--render] [--report]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Exception class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--render` |  | `false` | Add an empty `render()` method. |
| `--report` |  | `false` | Add an empty `report()` method. |

**Notes**

- Writes `<Name>.php` to the `exceptions` generator path (`paths.generator.exceptions.path`, default `app/Exceptions`).
- Stub: `exception.stub`, `exception-render.stub` (`--render`), `exception-report.stub` (`--report`) or `exception-render-report.stub` (both).

```bash
php artisan module:make-exception PostNotFound Blog
# Modules/Blog/app/Exceptions/PostNotFound.php
```

## module:make-factory

Creates a model factory in a module.

```bash
php artisan module:make-factory <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Model name. The command appends `Factory`. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>Factory.php` to the `factory` generator path (`paths.generator.factory.path`, default `database/factories`).
- Stub: `factory.stub`. The model namespace comes from the `model` generator path.

```bash
php artisan module:make-factory Post Blog
# Modules/Blog/database/factories/PostFactory.php
```

## module:make-helper

Creates a helper class in a module.

```bash
php artisan module:make-helper <name> [<module>] [-i|--invokable]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Helper class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--invokable` | `-i` | `false` | Generate a class with a single `__invoke()` method. |

**Notes**

- Writes `<Name>.php` to the `helpers` generator path (`paths.generator.helpers.path`, default `app/Helpers`).
- Stub: `helper.stub`, or `helper-invoke.stub` with `--invokable`.

```bash
php artisan module:make-helper Str Blog
# Modules/Blog/app/Helpers/Str.php
```

## module:make-health-indicator

Creates a health indicator class for [`nwidart/laravel-actuator`](https://github.com/nWidart/laravel-actuator) in a module.

```bash
php artisan module:make-health-indicator <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Health indicator class name. The command adds no suffix. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>.php` to `paths.generator.health.path` when set, otherwise `<paths.app_folder>Health` (default `app/Health`, namespace `Modules\<Module>\Health`). See [Configuration](/laravel-modules/reference/configuration/#default-entries).
- Each segment of a nested name is converted to StudlyCase, for the folder and for the namespace: `api/v2/Payments` gives `app/Health/Api/V2/Payments.php` in the namespace `Modules\Blog\Health\Api\V2`.
- Stub: `health-indicator.stub`.
- The command works without the actuator installed, but the generated class needs the actuator to be used.
- The command does not edit the module service provider. It prints the line to add to the `$healthIndicators` property. The suggested key is the class name without a trailing `HealthIndicator`, in camel case. See [Report module health with the actuator](/laravel-modules/guides/production/health-checks/).

```bash
php artisan module:make-health-indicator PaymentsHealthIndicator Blog
# Modules/Blog/app/Health/PaymentsHealthIndicator.php
#
# INFO  Register it as [module:blog:payments] in the $healthIndicators property of the module service provider:
#   'payments' => \Modules\Blog\Health\PaymentsHealthIndicator::class,
```

## module:make-inertia-component

Creates an Inertia component in a module.

```bash
php artisan module:make-inertia-component <name> [<module>] [--vue] [--react] [--svelte]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Inertia component name. A name with `/` or `\` creates sub-directories under the component folder. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--vue` |  | `false` | Create a Vue file. |
| `--react` |  | `false` | Create a React file. |
| `--svelte` |  | `false` | Create a Svelte file. |

**Notes**

- Writes `<Name>.vue`, `<Name>.jsx` or `<Name>.svelte` to the `inertia-components` generator path (`paths.generator.inertia-components.path`, default `resources/js/Components`).
- Stub: `inertia/component-vue.stub`, `inertia/component-react.stub` or `inertia/component-svelte.stub`.
- Framework comes from the flag given, else `inertia.frontend` (default `vue`). Passing more than one of `--vue`, `--react`, and `--svelte` writes nothing, exits with `1`, and prints an error (for example `The --react and --svelte options cannot be used together. Pass only one of --vue, --react or --svelte.`).

```bash
php artisan module:make-inertia-component Posts/Index Blog --react
# Modules/Blog/resources/js/Components/Posts/Index.jsx
```

## module:make-inertia-page

Creates an Inertia page in a module.

```bash
php artisan module:make-inertia-page <name> [<module>] [--vue] [--react] [--svelte]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Inertia page name. A name with `/` or `\` creates sub-directories under the page folder. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--vue` |  | `false` | Create a Vue file. |
| `--react` |  | `false` | Create a React file. |
| `--svelte` |  | `false` | Create a Svelte file. |

**Notes**

- Writes `<Name>.vue`, `<Name>.jsx` or `<Name>.svelte` to the `inertia` generator path (`paths.generator.inertia.path`, default `resources/js/Pages`).
- Stub: `inertia/page-vue.stub`, `inertia/page-react.stub` or `inertia/page-svelte.stub`.
- Framework comes from the flag given, else `inertia.frontend` (default `vue`). Passing more than one of `--vue`, `--react`, and `--svelte` writes nothing, exits with `1`, and prints an error (for example `The --react and --svelte options cannot be used together. Pass only one of --vue, --react or --svelte.`).

```bash
php artisan module:make-inertia-page Posts/Index Blog --react
# Modules/Blog/resources/js/Pages/Posts/Index.jsx
```

## module:make-interface

Creates an interface in a module.

```bash
php artisan module:make-interface <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Interface name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>.php` to the `interfaces` generator path (`paths.generator.interfaces.path`, default `app/Interfaces`).
- Stub: `interface.stub`.

```bash
php artisan module:make-interface PostRepository Blog
# Modules/Blog/app/Interfaces/PostRepository.php
```

## module:make-job

Creates a queued job class in a module.

```bash
php artisan module:make-job <name> [<module>] [--sync]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Job class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--sync` |  | `false` | Generate a synchronous job. |

**Notes**

- Writes `<Name>.php` to the `jobs` generator path (`paths.generator.jobs.path`, default `app/Jobs`).
- Stub: `job-queued.stub`, or `job.stub` with `--sync`.

```bash
php artisan module:make-job PublishPost Blog
# Modules/Blog/app/Jobs/PublishPost.php
```

## module:make-job-middleware

Creates a job middleware class in a module.

```bash
php artisan module:make-job-middleware <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Job middleware class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>.php` to the `job-middleware` generator path (`paths.generator.job-middleware.path`, default `app/Jobs/Middleware`).
- Stub: `job-middleware.stub`.
- When `paths.generator.job-middleware` has no path, the file goes to a `Middleware` folder inside the `jobs` generator path.

```bash
php artisan module:make-job-middleware RateLimited Blog
# Modules/Blog/app/Jobs/Middleware/RateLimited.php
```

## module:make-listener

Creates an event listener in a module.

```bash
php artisan module:make-listener <name> [<module>] [-e|--event=EVENT] [--queued]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Listener class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--event` | `-e` | none | Event class the listener handles. |
| `--queued` |  | `false` | Generate a queued listener. |

**Notes**

- Writes `<Name>.php` to the `listener` generator path (`paths.generator.listener.path`, default `app/Listeners`).
- Stub: `listener.stub` with `--event`, or `listener-duck.stub` without it (untyped `$event`). With `--queued`: `listener-queued.stub` or `listener-queued-duck.stub`.
- An event starting with `\`, the application namespace, `Illuminate\`, or the modules namespace is used as a fully qualified class name. Other values resolve to `<modules namespace>\<Module>\<event namespace>\<event>`.

```bash
php artisan module:make-listener SendNotification Blog --event=PostPublished --queued
# Modules/Blog/app/Listeners/SendNotification.php
```

### Listener discovery

Laravel discovers listeners only in `app/Listeners` by default; `Modules/<Name>/app/Listeners` is not added automatically. Module listeners are registered either via `$listen` on the module `EventServiceProvider` or by adding module listener paths to Laravel event discovery (`bootstrap/app.php` `withEvents(discover: ...)` or `EventServiceProvider::addEventDiscoveryPaths()`).

- `LaravelModulesServiceProvider` registers `Nwidart\Modules\Support\ListenerClassNameGuesser` so discovered files under `Modules/<Name>/app/Listeners/` resolve to `Modules\<Name>\Listeners\...`.
- Glob discovery paths match disabled modules too.
- When using `php artisan event:cache`, re-cache after enabling or disabling a module.
- Listing a discovered listener in `$listen` registers it twice.

For setup steps, see [Register events and listeners](/laravel-modules/guides/console-and-events/events-and-listeners/).

## module:make-mail

Creates a mailable class in a module.

```bash
php artisan module:make-mail <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Mailable class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>.php` to the `emails` generator path (`paths.generator.emails.path`, default `app/Emails`).
- Stub: `mail.stub`.

```bash
php artisan module:make-mail WelcomeMail Blog
# Modules/Blog/app/Emails/WelcomeMail.php
```

## module:make-middleware

Creates a middleware class in a module.

```bash
php artisan module:make-middleware <name> [<module>] [--inertia]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Middleware class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--inertia` |  | `false` | Generate an Inertia `HandleInertiaRequests` middleware. |

**Notes**

- Writes `<Name>.php` to the `filter` generator path (`paths.generator.filter.path`, default `app/Http/Middleware`).
- Stub: `middleware.stub`, or `middleware/handle-inertia-requests.stub` with `--inertia`.

```bash
php artisan module:make-middleware EnsureIsAdmin Blog
# Modules/Blog/app/Http/Middleware/EnsureIsAdmin.php
```

## module:make-migration

Creates a migration in a module.

```bash
php artisan module:make-migration <name> [<module>] [--fields=FIELDS] [--plain]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Migration name, in snake case, for example `create_posts_table`. The command uses it as given. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--fields` |  | none | Columns, comma-separated, for example `title:string, body:text`. Ignored with `--plain`. |
| `--plain` |  | `false` | Use `migration/plain.stub` (empty `up()` and `down()`) whatever the name is. |

**Notes**

- Writes `<Y_m_d_His>_<name>.php` to the `migration` generator path (`paths.generator.migration.path`, default `database/migrations`). All five shipped stubs generate anonymous classes (`return new class extends Migration`); the studly-case name is still passed as `$CLASS$` for custom stubs.
- Stub selection: `--plain` always uses `migration/plain.stub`. Otherwise the first word of `name` selects the stub:
  - `create`, `make` -> `migration/create.stub`
  - `add`, `update`, `append`, `insert` -> `migration/add.stub`
  - `delete`, `remove` -> `migration/delete.stub`
  - `destroy`, `drop` -> `migration/drop.stub`
  - any other word -> `migration/plain.stub`
- The table name comes from the rest of the name: `create_posts_table` gives `posts`, `add_slug_to_posts_table` gives `posts`.
- The generated column lines are indented with four spaces per level (12 spaces inside `Schema::create`), without tab characters or a blank line before `});`.
- Each field is `column:type[:modifier...]`. The shortcuts `soft_delete` and `remember_token`, and `belongsTo:column:key:table`, are also accepted.

```bash
php artisan module:make-migration create_posts_table Blog --fields="title:string, body:text"
# Modules/Blog/database/migrations/2026_01_01_000000_create_posts_table.php
```

## module:make-model

Creates an Eloquent model in a module, and optionally the files that go with it.

```bash
php artisan module:make-model <model> [<module>] [-a|--all] [--attributes] [-c|--controller] [--fillable=FILLABLE] [-f|--factory] [-m|--migration] [-r|--request] [-R|--resource] [-p|--policy] [-s|--seed] [--force]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `model` | yes | Model class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | `-a` | `false` | Same as `-c -f -m -r -R -p -s`. |
| `--attributes` |  | `false` | Use Eloquent attributes such as `#[Fillable]` instead of model properties. |
| `--controller` | `-c` | `false` | Also run `module:make-controller <Model>Controller`. |
| `--fillable` |  | none | Comma-separated fillable attributes. |
| `--factory` | `-f` | `false` | Also run `module:make-factory <Model>`, and add the factory to the model. |
| `--migration` | `-m` | `false` | Also run `module:make-migration create_<plural snake name>_table`. |
| `--request` | `-r` | `false` | Also run `module:make-request <Model>Request`. |
| `--resource` | `-R` | `false` | Also run `module:make-resource <Model>Resource`. |
| `--policy` | `-p` | `false` | Also run `module:make-policy <Model>Policy`. |
| `--seed` | `-s` | `false` | Also run `module:make-seed <Model>Seeder`. |
| `--force` |  | `false` | Overwrite the model when it exists. No shortcut: `-f` is `--factory`. |

**Notes**

- Writes `<Model>.php` to the `model` generator path (`paths.generator.model.path`, default `app/Models`).
- Stub: `model.stub`, with the sections `ATTRIBUTES`, `FACTORY` and `PROPERTIES`.
- The related commands run after the model is written, and only when the model was written. They receive no `--force`. A migration name for `BlogPost` is `create_blog_posts_table`.
- The command runs every related command it was asked for. It exits with `1` when one of them fails, for example when the migration or controller file already exists.
- `--fillable=title,body` writes `['title','body']` to the model.

```bash
php artisan module:make-model Post Blog -mfc
# Modules/Blog/app/Models/Post.php, plus a migration, a factory and a controller
```

## module:make-notification

Creates a notification class in a module.

```bash
php artisan module:make-notification <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Notification class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>.php` to the `notifications` generator path (`paths.generator.notifications.path`, default `app/Notifications`).
- Stub: `notification.stub`.

```bash
php artisan module:make-notification PostPublished Blog
# Modules/Blog/app/Notifications/PostPublished.php
```

## module:make-observer

Creates a model observer in a module.

```bash
php artisan module:make-observer <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Model name. The command appends `Observer`. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>Observer.php` to the `observer` generator path (`paths.generator.observer.path`, default `app/Observers`).
- Stub: `observer.stub`. The model namespace comes from the `model` generator path.

```bash
php artisan module:make-observer Post Blog
# Modules/Blog/app/Observers/PostObserver.php
```

## module:make-policy

Creates a policy class in a module.

```bash
php artisan module:make-policy <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Policy class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>.php` to the `policies` generator path (`paths.generator.policies.path`, default `app/Policies`).
- Stub: `policy.plain.stub`.

```bash
php artisan module:make-policy PostPolicy Blog
# Modules/Blog/app/Policies/PostPolicy.php
```

## module:make-provider

Creates a service provider in a module.

```bash
php artisan module:make-provider <name> [<module>] [--master]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Service provider class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--master` |  | `false` | Generate the main provider of the module, which registers the other providers. |

**Notes**

- Writes `<Name>.php` to the `provider` generator path (`paths.generator.provider.path`, default `app/Providers`).
- Stub: `provider.stub`, or `scaffold/provider.stub` with `--master`.

```bash
php artisan module:make-provider BlogServiceProvider Blog
# Modules/Blog/app/Providers/BlogServiceProvider.php
```

## module:make-replacement

Creates a replacement key class for stubs in a module.

```bash
php artisan module:make-replacement <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Replacement class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>.php` to the `command_replacements` generator path (`paths.generator.command_replacements.path`, default `app/Console/Replacements`).
- Stub: `replacement.stub`.
- The generated class extends `Nwidart\Modules\Support\ReplacementKeyCommand`. Its `handle()` body returns `strtolower($this->generator->getName())`, the lower-case module name. See [Generating a class](/laravel-modules/reference/stubs/#generating-a-class).

```bash
php artisan module:make-replacement AppUrl Blog
# Modules/Blog/app/Console/Replacements/AppUrl.php
```

## module:make-repository

Creates a repository class in a module.

```bash
php artisan module:make-repository <name> [<module>] [-i|--invokable]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Repository class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--invokable` | `-i` | `false` | Generate a class with a single `__invoke()` method. |

**Notes**

- Writes `<Name>.php` to the `repository` generator path (`paths.generator.repository.path`, default `app/Repositories`).
- Stub: `repository.stub`, or `repository-invoke.stub` with `--invokable`.

```bash
php artisan module:make-repository PostRepository Blog
# Modules/Blog/app/Repositories/PostRepository.php
```

## module:make-request

Creates a form request class in a module.

```bash
php artisan module:make-request <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Form request class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>.php` to the `request` generator path (`paths.generator.request.path`, default `app/Http/Requests`).
- Stub: `request.stub`.

```bash
php artisan module:make-request StorePostRequest Blog
# Modules/Blog/app/Http/Requests/StorePostRequest.php
```

## module:make-resource

Creates an API resource class in a module.

```bash
php artisan module:make-resource <name> [<module>] [-c|--collection] [-j|--json-api]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Resource class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--collection` | `-c` | `false` | Generate a resource collection. |
| `--json-api` | `-j` | `false` | Generate a JSON:API resource. |

**Notes**

- Writes `<Name>.php` to the `resource` generator path (`paths.generator.resource.path`, default `app/Transformers`).
- Stub: `resource-collection.stub` for `--collection` or a name that ends with `Collection`, else `resource-json-api.stub` for `--json-api`, else `resource.stub`.

```bash
php artisan module:make-resource PostResource Blog
# Modules/Blog/app/Transformers/PostResource.php
```

## module:make-rule

Creates a validation rule in a module.

```bash
php artisan module:make-rule <name> [<module>] [-i|--implicit]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Rule class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--implicit` | `-i` | `false` | Generate an implicit rule. |

**Notes**

- Writes `<Name>.php` to the `rules` generator path (`paths.generator.rules.path`, default `app/Rules`).
- Stub: `rule.stub`, or `rule.implicit.stub` with `--implicit`.

```bash
php artisan module:make-rule Uppercase Blog
# Modules/Blog/app/Rules/Uppercase.php
```

## module:make-scope

Creates an Eloquent scope class in a module.

```bash
php artisan module:make-scope <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Scope class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>.php` to the `scopes` generator path (`paths.generator.scopes.path`, default `app/Models/Scopes`).
- Stub: `scope.stub`.
- When `paths.generator.scopes` has no path, the file goes to a `Scopes` folder inside the `model` generator path.

```bash
php artisan module:make-scope PublishedScope Blog
# Modules/Blog/app/Models/Scopes/PublishedScope.php
```

## module:make-seed

Creates a database seeder in a module.

```bash
php artisan module:make-seed <name> [<module>] [--master]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Seeder name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--master` |  | `false` | Generate the main seeder of the module (`<Name>DatabaseSeeder`). |

**Notes**

- Writes `<Name>Seeder.php` to the `seeder` generator path (`paths.generator.seeder.path`, default `database/seeders`).
- Stub: `seeder.stub`.
- The command appends `Seeder` unless the name already contains `Seeder`. With `--master` it appends `Database` first.
- A name that resolves to the class `Seeder` is rejected: it clashes with the `Illuminate\Database\Seeder` base class.
- The command clears the module cache before it writes.

```bash
php artisan module:make-seed Post Blog
# Modules/Blog/database/seeders/PostSeeder.php
```

## module:make-service

Creates a service class in a module.

```bash
php artisan module:make-service <name> [<module>] [-i|--invokable]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Service class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--invokable` | `-i` | `false` | Generate a class with a single `__invoke()` method. |

**Notes**

- Writes `<Name>.php` to the `services` generator path (`paths.generator.services.path`, default `app/Services`).
- Stub: `service.stub`, or `service-invoke.stub` with `--invokable`.

```bash
php artisan module:make-service PostPublisher Blog
# Modules/Blog/app/Services/PostPublisher.php
```

## module:make-test

Creates a test class in a module.

```bash
php artisan module:make-test <name> [<module>] [--feature]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Test class name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--feature` |  | `false` | Create a feature test instead of a unit test. |

**Notes**

- Writes `<Name>.php` to the `test-unit` generator path (`paths.generator.test-unit.path`, default `tests/Unit`), or to the `test-feature` path (`paths.generator.test-feature.path`, default `tests/Feature`) with `--feature`.
- Stub: `tests/unit.stub`, or `tests/feature.stub` with `--feature`.
- Namespace: the module namespace plus the generator path, without the app folder. With the default paths, `Modules\Blog\Tests\Unit` or `Modules\Blog\Tests\Feature`. With `paths.app_folder` set to `app/` and a test path of `app/Tests/Unit`, the namespace is `Modules\Blog\Tests\Unit`.

```bash
php artisan module:make-test PostTest Blog --feature
# Modules/Blog/tests/Feature/PostTest.php
```

## module:make-trait

Creates a trait in a module.

```bash
php artisan module:make-trait <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Trait name. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<Name>.php` to the `traits` generator path (`paths.generator.traits.path`, default `app/Traits`).
- Stub: `trait.stub`.

```bash
php artisan module:make-trait HasSlug Blog
# Modules/Blog/app/Traits/HasSlug.php
```

## module:make-view

Creates a Blade view in a module.

```bash
php artisan module:make-view <name> [<module>]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | View name. The file name is the name in lower case. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

This command has no options besides `--force`.

**Notes**

- Writes `<name in lower case>.blade.php` to the `views` generator path (`paths.generator.views.path`, default `resources/views`).
- Stub: `view.stub`.

```bash
php artisan module:make-view index Blog
# Modules/Blog/resources/views/index.blade.php
```

## module:route-provider

Creates the `RouteServiceProvider` of a module.

```bash
php artisan module:route-provider [<module>] [--force]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--force` |  | `false` | Overwrite the file when it exists. No shortcut. |

**Notes**

- The command name has no `make-` prefix and the command has no `name` argument.
- Writes `RouteServiceProvider.php` to the `provider` generator path (`paths.generator.provider.path`, default `app/Providers`).
- Stub: `route-provider.stub`.
- `WEB_ROUTES` and `API_ROUTES` stay only when `routes.generate` is `true` and `stubs.files` lists `routes/web` or `routes/api`.

```bash
php artisan module:route-provider Blog
# Modules/Blog/app/Providers/RouteServiceProvider.php
```

## module:generators

Lists every generator: `module:make` and every registered command that extends `Nwidart\Modules\Commands\Make\GeneratorCommand`. The package ships 43. A `GeneratorCommand` subclass in your application appears too, in the group `other`. The list is sorted by name and includes hidden commands.

```bash
php artisan module:generators [--json]
```

**Arguments**

This command has no arguments.

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--json` |  | `false` | Print the generators as one JSON document. See [JSON output](/laravel-modules/reference/commands/#json-output). |

**Notes**

- Without `--json`, the command prints a table with the columns `Command`, `Group` and `Description`.
- With `--json`, the command follows the [JSON output](/laravel-modules/reference/commands/#json-output) rules. The document is an object with one key, `generators`.
- Application-wide options (`--help`, `--quiet`, `--verbose`, `--ansi`, `--no-interaction`, `--env` and the like) are not listed.

**Groups**

| Group | Generators |
| --- | --- |
| `module` | `module:make` |
| `data` | `module:make-model`, `-migration`, `-factory`, `-seed`, `-observer`, `-scope`, `-cast`, `-policy`, `-repository` |
| `http` | `module:make-controller`, `-request`, `-resource`, `-middleware`, `-rule`, and `module:route-provider` |
| `events` | `module:make-event`, `-listener`, `-job`, `-job-middleware`, `-notification`, `-mail`, `-channel`, `-event-provider` |
| `views` | `module:make-view`, `-component`, `-component-view`, `-inertia-page`, `-inertia-component` |
| `classes` | `module:make-class`, `-action`, `-service`, `-interface`, `-trait`, `-enum`, `-helper`, `-exception` |
| `console` | `module:make-command`, `-config`, `-provider`, `-replacement`, `-health-indicator` |
| `tests` | `module:make-test` |
| `other` | A generator that is not in the groups above, such as one from your application. |

**JSON fields**

Each item of `generators` has these fields:

| Field | Type | Description |
| --- | --- | --- |
| `name` | string | Command name, such as `module:make-model`. |
| `group` | string | One of the groups above. |
| `description` | string | The command description. |
| `arguments` | array | The arguments of the command, in order. |
| `options` | array | The options of the command, without the application-wide options. |

Each item of `arguments`:

| Field | Type | Description |
| --- | --- | --- |
| `name` | string | Argument name. |
| `required` | boolean | `true` when the argument must be given. |
| `array` | boolean | `true` when the argument takes several values. |
| `default` | mixed | Default value, or `null`. |
| `description` | string | Argument description. |

Each item of `options`:

| Field | Type | Description |
| --- | --- | --- |
| `name` | string | Option name, without the leading `--`. |
| `shortcut` | string or null | The shortcut letter without the dash, or `null`. |
| `accepts_value` | boolean | `true` when the option takes a value. |
| `value_required` | boolean | `true` when the option needs a value once given. |
| `array` | boolean | `true` when the option can repeat. |
| `negatable` | boolean | `true` when a `--no-<name>` form exists. |
| `default` | mixed | Default value: `false` for a flag, `null` for no default. |
| `description` | string | Option description. |

The document is one line. This example shows one generator, pretty-printed and with some of its options:

```json
{
  "generators": [
    {
      "name": "module:make-model",
      "group": "data",
      "description": "Create a new model for the specified module.",
      "arguments": [
        {
          "name": "model",
          "required": true,
          "array": false,
          "default": null,
          "description": "The name of model will be created."
        },
        {
          "name": "module",
          "required": false,
          "array": false,
          "default": null,
          "description": "The name of module will be used."
        }
      ],
      "options": [
        {
          "name": "fillable",
          "shortcut": null,
          "accepts_value": true,
          "value_required": false,
          "array": false,
          "negatable": false,
          "default": null,
          "description": "The fillable attributes."
        },
        {
          "name": "force",
          "shortcut": null,
          "accepts_value": false,
          "value_required": false,
          "array": false,
          "negatable": false,
          "default": false,
          "description": "Overwrite the file if it already exists"
        },
        {
          "name": "dry-run",
          "shortcut": null,
          "accepts_value": false,
          "value_required": false,
          "array": false,
          "negatable": false,
          "default": false,
          "description": "List the files the command would write, and write nothing"
        },
        {
          "name": "json",
          "shortcut": null,
          "accepts_value": false,
          "value_required": false,
          "array": false,
          "negatable": false,
          "default": false,
          "description": "Print the result as one JSON document"
        }
      ]
    }
  ]
}
```

List the names of the generators in the `data` group:

```bash
php artisan module:generators --json | jq -r '.generators[] | select(.group == "data") | .name'
```
