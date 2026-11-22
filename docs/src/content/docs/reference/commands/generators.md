---
title: Generator commands
description: Artisan commands that create modules and generate classes and files inside a module.
---

This page lists `module:make`, the 41 `module:make-*` generators and `module:route-provider`. Commands that manage existing modules are in [Module management commands](/laravel-modules/reference/commands/module-management/).

## Shared behaviour

Apart from `module:make`, which creates a whole module, every command on this page is a generator: it creates one file in a module. Two generators write more than one file. `module:make-model` also runs other generators for its flags (`--controller`, `--factory`, `--migration`, `--request`, `--resource`, `--policy`, `--seed`), and `module:make-component` also runs `module:make-component-view`.

### Arguments

- `name` is the first argument. Two generators name it differently: `module:make-controller` uses `controller` and `module:make-model` uses `model`. `module:make-event-provider` and `module:route-provider` have no name argument.
- `module` is optional on every generator except `module:make-class`, where it is required. When `module` is empty, the command uses the module stored by [`module:use`](/laravel-modules/reference/commands/module-management/#moduleuse). If no module is stored, or the stored module no longer exists, the command prints `No module given, and no module is set with module:use. Pass the module name, or run php artisan module:use <module> first.`, writes nothing and exits with `1`.
- An unknown module name prints `Module [Nope] does not exist.`, writes nothing and exits with `1`.
- Generators do not check whether the module is enabled.

### Name handling

- The class name is the studly-case `name`. A `name` with `/` or `\` puts the file in a sub-directory of the generator path and the class in the matching sub-namespace: `Admin/Report` gives the class `Report` in the `Admin` sub-namespace.
- An empty name, an absolute path, or a name with a `..` segment prints an error. A segment that becomes `..` once whitespace, hyphens and underscores are removed (for example `. .`) counts as a `..` segment and exits with `1`.
- The command creates missing directories.

### Path and namespace

- The target folder is `<module path>/<paths.generator.<key>.path>`. The `<key>` of each command is in its notes. See [Configuration](/laravel-modules/reference/configuration/#pathsgenerator).
- The namespace is `<namespace>\<Module>\<generator namespace>`. The generator namespace is `paths.generator.<key>.namespace` when set, otherwise the generator path without `paths.app_folder`.
- The `generate` flag of a generator key does not affect a generator command. It controls what `module:make` creates.

### Existing files

- A file that exists stops the command with `File : <path> already exists.` and exit status `1`.
- `--force` overwrites the file. Each generator accepts `--force` with the shortcut `-f`, except `module:make-model` (`-f` is `--factory`), `module:route-provider` and `module:make`, which define `--force` without a shortcut. The per-command tables do not repeat `--force`, except where it differs.

### Exit status

`0` on success. `1` on an invalid name, an unknown or missing module, an existing file or any other error.

## module:make

Creates one or more modules: the folder structure, `module.json`, `composer.json` and the starting classes.

```bash
php artisan module:make <name>... [-p|--plain] [--api] [--web] [--inertia] [-d|--disabled] [--force] [--author-name=AUTHOR-NAME] [--author-email=AUTHOR-EMAIL] [--author-vendor=AUTHOR-VENDOR]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes, asked when missing | One or more module names. Repeatable. In an interactive terminal, a missing argument opens the prompt `What is the name of the module?`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--plain` | `-p` | `false` | Generate a plain module: folders and `module.json` only. |
| `--api` |  | `false` | Generate an API module. The controller is generated with `module:make-controller --api` (the `controller-api.stub` stub). Everything else is the same as in a web module: the views, `routes/web.php`, `routes/api.php`, the assets and the other `stubs.files` are still written. |
| `--web` |  | `false` | Generate a web module (default). Wins over `--plain` and `--api`. |
| `--inertia` |  | `false` | Generate an Inertia module: an Inertia controller and four Inertia pages, no Blade views. |
| `--disabled` | `-d` | `false` | Create the module in the disabled state. |
| `--force` |  | `false` | Recreate the module when it already exists. |
| `--author-name` |  | `composer.author.name` | Author name written to `composer.json`. |
| `--author-email` |  | `composer.author.email` | Author email written to `composer.json`. |
| `--author-vendor` |  | `composer.vendor` | Vendor part of the package name in `composer.json`. |

**Notes**

- This command is not a generator in the sense of the shared behaviour above: it has no `module` argument and does not read `module:use`. Without `name`, an interactive terminal asks `What is the name of the module?`.
- The name is converted to studly case. It becomes the module folder and namespace. The converted name must match `^[A-Za-z_][A-Za-z0-9_]*$`. A name that does not match prints an error, the command goes on with the next name, and the exit status is `1`.
- Type, first match wins: `--web` gives `web`; `--plain` with `--api` gives `web`; `--plain` alone gives `plain`; `--api` alone gives `api`; no type option gives `web`. `--inertia` has no effect on a plain module.
- An existing module stops with `Module [<name>] already exists!` unless `--force` is given. With `--force`, the command deletes the existing module and creates it again.
- Before it writes anything, the command renders every stub. A JSON stub that renders invalid JSON stops the command, and the existing module stays in place.
- If `composer.json` is generated and uses the app folder, every class generator path must be inside `paths.app_folder` (or match its namespace when `paths.app_folder` is empty). Otherwise the command prints the offending keys and stops.
- Steps, in order: (1) create the folder of every `paths.generator` entry whose `generate` is `true`, with a `.gitkeep` file when `stubs.gitkeep` is `true`; (2) write `module.json`; (3) unless the type is `plain`, write the files of `stubs.files` (see [Configuration](/laravel-modules/reference/configuration/#stubs)) and then run the resource generators below; (4) set the module status.
- Resource generators, unless the type is `plain`: `module:make-seed <Name> --master` (the main seeder, `<Name>DatabaseSeeder`) when `seeder.generate` is `true`; `module:make-provider <Name>ServiceProvider --master` (the main provider, which registers the other providers) when `provider.generate` is `true`, otherwise the command removes the `providers` entry of `module.json`; `module:make-event-provider` and `module:route-provider` when their `generate` flag is `true` (a missing `event-provider` key follows `provider`); `module:make-controller <Name>Controller` when `controller.generate` is `true`, with `--api` for an API module and `--inertia` for an Inertia module; `module:make-inertia-page` for `Index`, `Create`, `Show` and `Edit` with `--inertia`.
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
php artisan module:make-class <name> <module> [-t|--type=TYPE] [-s|--suffix] [-i|--invokable] [-f|--force]
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
| `--force` | `-f` | `false` | Overwrite the file when it exists. |

**Notes**

- Stub: `class.stub`, or `class-invoke.stub` with `--invokable`. The type is made singular.
- With the type `class`, the file goes to the `class` generator path (default `app/Classes`). With any other type, the last `Classes` segment of that path is replaced by the plural studly-case type, for example `app/Services` for `--type=service`. The `services` generator key is not read for the path.
- The namespace is `paths.generator.<plural type>.namespace`, else `paths.generator.<type>.namespace`, else the namespace of the computed path.
- With `--suffix`, the command removes the plural or singular type from the end of the name, then appends the studly-case type: `Mailer` and `MailerService` with `--type=service --suffix` both give `MailerService`.

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
- Stub: `controller-plain.stub` for `--plain`, else `controller-api.stub` for `--api`, else `controller.invokable.stub` for `--invokable`, else `controller-inertia.stub` for `--inertia`, else `controller.stub`. The first matching option wins.

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
- The provider overrides `configureEmailVerification()` with an empty body. The application already registers `SendEmailVerificationNotification` on `Registered`; without the override, each module provider registers it again and users get one extra verification email per module.

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
php artisan module:make-health-indicator <name> [<module>] [--force]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `name` | yes | Health indicator class name. The command adds no suffix. |
| `module` | no | Module name. Falls back to the module set by `module:use`. |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--force` | `-f` | `false` | Overwrite the file if it already exists. |

**Notes**

- Writes `<Name>.php` to the `health` generator path. When `modules.paths.generator.health` is not set, the path is `paths.app_folder` plus `Health` (`app/Health` with the default app folder), in the namespace `Modules\<Module>\Health`. When the application sets `paths.generator.health.path` (and optionally `namespace`), that path is used. See [Configuration](/laravel-modules/reference/configuration/#default-entries).
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
- The framework is the option given, else `inertia.frontend` (default `vue`). Pass at most one of `--vue`, `--react` and `--svelte`. With more than one, the command writes nothing, exits with `1` and prints, for example, `The --react and --svelte options cannot be used together. Pass only one of --vue, --react or --svelte.`

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
- The framework is the option given, else `inertia.frontend` (default `vue`). Pass at most one of `--vue`, `--react` and `--svelte`. With more than one, the command writes nothing, exits with `1` and prints, for example, `The --react and --svelte options cannot be used together. Pass only one of --vue, --react or --svelte.`

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
- Stub: `listener.stub` with `--event`, `listener-duck.stub` without it (a "duck" listener has an untyped `$event` parameter); the queued variants are `listener-queued.stub` and `listener-queued-duck.stub`.
- An event that starts with `\`, the application namespace, `Illuminate\` or the modules namespace is used as a fully qualified class name. Any other value resolves to `<modules namespace>\<Module>\<event namespace>\<event>`.

```bash
php artisan module:make-listener SendNotification Blog --event=PostPublished --queued
# Modules/Blog/app/Listeners/SendNotification.php
```

### Listener discovery

Laravel discovers listeners only in `app/Listeners`. It does not discover `Modules/<Name>/app/Listeners`. Register a module listener in one of two ways:

- List it in the `$listen` array of the module `EventServiceProvider`.
- Add the module listener directories to the application's discovery paths in `bootstrap/app.php`:

```php
->withEvents(discover: [
    __DIR__.'/../app/Listeners',
    __DIR__.'/../Modules/*/app/Listeners',
])
```

`EventServiceProvider::addEventDiscoveryPaths()` adds the same paths. The package registers `Nwidart\Modules\Support\ListenerClassNameGuesser`, so Laravel resolves `Modules/Blog/app/Listeners/SyncPoints.php` to the class `Modules\Blog\Listeners\SyncPoints`.

- The glob also matches disabled modules.
- With `php artisan event:cache`, run it again after you enable or disable a module.
- A listener that is both discovered and listed in a module `$listen` array is registered twice. Use one way per listener.

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

- Writes `<Y_m_d_His>_<name>.php` to the `migration` generator path (`paths.generator.migration.path`, default `database/migrations`). The class name is the studly-case name.
- Stub: with `--plain`, always `migration/plain.stub`. Without it, the first word of the name selects the stub. `create` or `make` gives `migration/create.stub`; `add`, `update`, `append` or `insert` gives `migration/add.stub`; `delete` or `remove` gives `migration/delete.stub`; `destroy` or `drop` gives `migration/drop.stub`; any other word gives `migration/plain.stub`.
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
- The `WEB_ROUTES` and `API_ROUTES` sections of the stub stay only when `stubs.files` lists `routes/web` or `routes/api` and the `routes` generator entry has `generate` set to `true`.

```bash
php artisan module:route-provider Blog
# Modules/Blog/app/Providers/RouteServiceProvider.php
```
