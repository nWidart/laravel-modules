---
title: Contracts and base classes
description: The interfaces in src/Contracts, the base classes and traits of the package, and the default FileActivator.
---

This page lists the interfaces of `Nwidart\Modules\Contracts`, the base classes of the package, its traits and the default activator. Method lists are complete for the public and protected API. The provider base class of a module has its own page: [Module service provider](/laravel-modules/reference/api/module-service-provider/).

## Interfaces

Each interface has the same layout: what it is and how the package uses it, then a table of its methods.

### RepositoryInterface

`Nwidart\Modules\Contracts\RepositoryInterface` is the contract of the module repository. The container binds it as a singleton, aliased as `modules`, to `Laravel\LaravelFileRepository`. Its docblock adds `setUsed()`, `forgetUsed()`, `getUsedNow()`, `update()` and `resetModules()`, which the package commands call: a custom repository must implement them too. Behaviour of every method: [Module facade and repository](/laravel-modules/reference/api/repository/).

| Signature | Returns | Description |
| --- | --- | --- |
| `all()` | `array<string, Module>` | Every module. |
| `scan()` | `array<string, Module>` | Scans the module folders. |
| `toCollection()` | `Collection<string, Module>` | The modules as a `Nwidart\Modules\Collection`. |
| `getScanPaths()` | `array<int, string>` | The scanned folders. |
| `allEnabled()` | `array<string, Module>` | The enabled modules. |
| `allDisabled()` | `array<string, Module>` | The disabled modules. |
| `count()` | `int` | The number of modules. |
| `getOrdered(string $direction = 'asc')` | `array<string, Module>` | The enabled modules ordered by priority. |
| `getByStatus(int\|bool $status)` | `array<string, Module>` | The modules with the given status. |
| `find(string $name)` | `Module\|null` | The module with that name. |
| `findOrFail(string $name)` | `Module` | Like `find()`. Throws `ModuleNotFoundException`. |
| `getModulePath(string $moduleName)` | `string` | The folder of a module, or the path it would have. |
| `getFiles()` | `Filesystem` | The filesystem instance. |
| `config(string $key, $default = null)` | `mixed` | A value of the `modules` config. |
| `getPath()` | `string` | The folder that holds the modules. |
| `boot()` | `void` | Deprecated. The package no longer calls it. |
| `register()` | `void` | Deprecated. The package no longer calls it. |
| `assetPath(string $module)` | `string` | The assets path of a module. |
| `delete(string $module)` | `bool` | Deletes a module. Throws `ModuleNotFoundException`. |
| `isEnabled(string $name)` | `bool` | `true` when the module is enabled. Throws `ModuleNotFoundException`. |
| `isDisabled(string $name)` | `bool` | `true` when the module is not enabled. Throws `ModuleNotFoundException`. |

### ActivatorInterface

`Nwidart\Modules\Contracts\ActivatorInterface` stores the enabled or disabled status of the modules. The container binds it as a singleton. The binding selects an activator with `modules.activator`, reads its class from `modules.activators.<modules.activator>.class` and constructs it with `new $class($app)`: the constructor receives the container. See [Configuration](/laravel-modules/reference/configuration/#activators).

| Signature | Returns | Description |
| --- | --- | --- |
| `enable(Module $module)` | `void` | Marks the module as enabled. |
| `disable(Module $module)` | `void` | Marks the module as disabled. |
| `hasStatus(Module\|string $module, bool $status)` | `bool` | `true` when the module has the given status. Accepts a module or a module name. |
| `setActive(Module $module, bool $active)` | `void` | Sets the status of a module. |
| `setActiveByName(string $name, bool $active)` | `void` | Sets the status of a module by name. `module:make` calls it. |
| `delete(Module $module)` | `void` | Deletes the stored status of a module. |
| `reset()` | `void` | Deletes every status the activator stores. |

### ConfirmableCommand

`Nwidart\Modules\Contracts\ConfirmableCommand` is a marker interface.

| Signature | Returns | Description |
| --- | --- | --- |
| none | | The interface declares no method. `BaseCommand` checks `instanceof` when it is constructed and when it runs. |

A [`BaseCommand`](#basecommand) that implements it:

- has the option `--force`, without a shortcut;
- runs two checks after it validated the module names, before any action: the prohibition check, then the confirmation. A failed check ends the command with exit status `1`, and no action runs;
- prohibition check: when the command is prohibited, it prints `This command is prohibited from running in this environment.`. `--force` does not lift the prohibition;
- confirmation: Laravel's `confirmToProceed()` prints the alert `getConfirmableLabel()` (default `Application In Production`) and asks `Are you sure you want to run this command?`. The default answer is no. `--force` skips the question. The question applies when `getConfirmableCallback()` returns `true`. With the default `null`, it applies in the `production` environment only. Without interactive input, the answer is the default: the command prints `Command cancelled.`.

| Command | Confirmation applies | Prohibition applies |
| --- | --- | --- |
| `module:migrate-fresh`, `module:migrate-refresh`, `module:migrate-reset`, `module:migrate-rollback` | In the `production` environment | After `Module::prohibitDestructiveCommands()` or its own `prohibit()` |
| `module:delete` | In every environment: its callback returns `true`, and its label is `Warning: Do you want to remove the module?` | After `Module::prohibitDestructiveCommands()` or its own `prohibit()` |

`Module::prohibitDestructiveCommands()` is the package counterpart of Laravel's `DB::prohibitDestructiveCommands()`. It calls the static `prohibit()` method of the `Illuminate\Console\Prohibitable` trait on the five commands in the table above (see [Destructive commands](/laravel-modules/reference/api/repository/#destructive-commands)). Each of the five commands uses the trait itself, so each has its own flag: `MigrateFreshCommand::prohibit()` prohibits only `module:migrate-fresh`.

`BaseCommand` also uses the trait. A third-party command that extends `BaseCommand`, implements `ConfirmableCommand` and does not use the trait itself shares the `BaseCommand` flag. `BaseCommand::prohibit()` or the command's own `::prohibit()` prohibits it. `Module::prohibitDestructiveCommands()` does not, and `BaseCommand::prohibit()` does not prohibit the five package commands. Commands that are not confirmable never run the check.

### PublisherInterface

`Nwidart\Modules\Contracts\PublisherInterface` is implemented by `Nwidart\Modules\Publishing\Publisher` for the asset, language and migration publishers.

| Signature | Returns | Description |
| --- | --- | --- |
| `publish()` | `void` | Publishes the files. |

### RunableInterface

`Nwidart\Modules\Contracts\RunableInterface` is implemented by `Nwidart\Modules\Process\Runner`.

| Signature | Returns | Description |
| --- | --- | --- |
| `run(string $command)` | `void` | Runs the command. |

## Base classes

### ModuleServiceProvider

`Nwidart\Modules\Support\ModuleServiceProvider` is the base class of the main provider of a module. Its properties, methods and what it registers (config, views, translations, migrations, commands, schedules, sub-providers and publish tags) are on [Module service provider](/laravel-modules/reference/api/module-service-provider/).

### BaseCommand

`Nwidart\Modules\Commands\BaseCommand` extends `Illuminate\Console\Command` and implements `PromptsForMissingInput`. It is the base class of the commands that run an action for each selected module. It adds the variadic `module` argument and the `--all` (`-a`) option, and runs `executeAction()` once for each module. For the behaviour, see [Shared behaviour](/laravel-modules/reference/commands/module-management/#shared-behaviour).

| Signature | Description |
| --- | --- |
| `const ALL = 'All'` | The label of the first entry of the module prompt. |
| `abstract executeAction($name)` | Public. Runs the action for one module name. Return `false` or a non-zero integer, or call `markAsFailed()`, to fail the run. The other modules still run. |
| `getInfo(): ?string` | Public. A line printed once before the actions. Default `null`. |
| `getConfirmableLabel(): ?string` | Public. The label of the production confirmation. Default `Application In Production`. Read only when the command implements `ConfirmableCommand`. |
| `getConfirmableCallback(): \Closure\|bool\|null` | Public. The callback that decides whether the confirmation applies. Default `null`. |
| `handle()` | Public. Checks the input, validates the module names, runs the actions and returns the exit code (`0`, or `1` after a failure or an unknown module name). |
| `hasModulesToRun(): bool` | Protected, from [`ValidatesModuleArgument`](#validatesmoduleargument). `true` when a module or `--all` was given. Otherwise prints `No module given. Pass a module name or --all.` and returns `false`. |
| `validateModules(): bool` | Protected, from [`ValidatesModuleArgument`](#validatesmoduleargument). `true` when every given module exists. Otherwise prints `Module [<name>] does not exist.` (or `Modules [<names>] do not exist.`) and returns `false`; `handle()` then returns `1` and runs no action. |
| `markAsFailed(): void` | Protected. Marks the run as failed. |
| `hasFailures(): bool` | Protected. `true` when an action failed during this run. |
| `getModuleModel(Module\|string $name)` | Protected. The module for a name. Throws `ModuleNotFoundException` for an unknown name. |
| `getModuleNames(InputInterface $input): array` | Protected. The names that `--all` and the `All` choice of the prompt stand for: every module, or, for a command with a `--direction` option, the enabled modules ordered by priority in that direction. `MigrateRefreshCommand` overrides it with `getEnabledModuleNames('asc')`. |
| `getEnabledModuleNames(string $direction = 'asc'): array` | Protected. The names of the enabled modules, ordered by `priority` in the given direction. |
| `moduleRepository()` | Protected. The repository, from [`InteractsWithModuleRepository`](#traits). |

The package registers the classes of `modules.commands` (see [Configuration](/laravel-modules/reference/configuration/)). A module provider registers the classes of its `$commands` property.

### GeneratorCommand

`Nwidart\Modules\Commands\Make\GeneratorCommand` extends `Illuminate\Console\Command` and is the base class of the `module:make-*` generators. It uses `InteractsWithModuleRepository` and `PathNamespace`. Its constructor adds the option `--force`, with the shortcut `-f` when no other option uses it, unless the command declares `--force` itself.

| Signature | Description |
| --- | --- |
| `$argumentName` | Protected property. The name of the argument that holds the class name. The default is an empty string, which turns off the name check. |
| `abstract getTemplateContents()` | Protected. Returns the content of the file, usually `Stub::create('/x.stub', [...])->render()`. |
| `abstract getDestinationFilePath()` | Protected. Returns the path of the file. |
| `handle(): int` | Public. Validates the name, creates the folder, writes the file through `FileGenerator`, and returns `0`. It prints an error and returns `1` for an invalid name or an existing file without `--force`. |
| `validateNameArgument(): ?string` | Protected. Returns an error message for an empty name, an absolute path or a path with a `..` segment, otherwise `null`. |
| `getClass()` | Public. The class name: `class_basename()` of the name argument. |
| `getDefaultNamespace(): string` | Public. The namespace part below the module namespace. Default an empty string. |
| `getClassNamespace($module)` | Public. The full namespace: the module namespace, `getDefaultNamespace()` and the folders in the name argument. |
| `module(?string $name = null): Module` | Public. The module for the name, or for `getModuleName()` when `$name` is `null`. |
| `getModuleName()` | Not defined by the class. Provided by [`ModuleCommandTrait`](#traits), which most generators use. |

The path and namespace of a generated class come from `modules.paths.generator.<key>`, read with `Support\Config\GenerateConfigReader`.

### ReplacementKeyCommand

`Nwidart\Modules\Support\ReplacementKeyCommand` is the base class of a replacement that `module:make` computes for a stub. A class is selected by an upper-case key in `modules.stubs.replacements.<stub>`. See [Stubs and placeholders](/laravel-modules/reference/stubs/#custom-replacement-classes).

| Signature | Description |
| --- | --- |
| `__construct(protected ModuleGenerator $generator)` | The generator that renders the stubs. |
| `abstract handle(): string` | Returns the text that replaces the key. `module:make` inserts it unchanged: in a JSON stub, escape it yourself. |

```php
use Nwidart\Modules\Support\ReplacementKeyCommand;

class CustomKey extends ReplacementKeyCommand
{
    public function handle(): string
    {
        return strtolower($this->generator->getName());
    }
}
```

`module:make-replacement` generates a class with this `handle()` body, which returns the lower-case module name. See [Stubs and placeholders](/laravel-modules/reference/stubs/#generating-a-class).

### Controller

`Nwidart\Modules\Routing\Controller` extends `Illuminate\Routing\Controller` and uses `AuthorizesRequests`, `DispatchesJobs` and `ValidatesRequests`. It adds no method. No stub or class of the package uses it: the generated controllers extend `App\Http\Controllers\Controller`.

## Traits

All traits are in `Nwidart\Modules\Traits`. Each has the same layout as the interfaces: a short description, then a table of its methods.

### PathNamespace

Path and namespace helpers. They build the class namespaces of the generators from the paths of `modules.paths`. All methods are public.

| Signature | Returns | Description |
| --- | --- | --- |
| `studly_path(string $path, $ds = '/')` | `string` | Studly-cases each segment of a path. |
| `studly_namespace(string $namespace, $ds = '\\')` | `string` | Studly-cases each segment of a namespace. |
| `path_namespace(string $path)` | `string` | The namespace of a path. |
| `module_namespace(string $module, ?string $path = null)` | `string` | The namespace of a module, with an optional extra path. |
| `clean_path(string $path, $ds = '/')` | `string` | Removes empty segments. |
| `strip_app_folder(?string $path)` | `string` | Removes the leading `modules.paths.app_folder` segment of a path. |
| `app_path(?string $path = null)` | `string` | The app folder of a module, with an optional path inside it. |

### ModuleCommandTrait

| Signature | Returns | Description |
| --- | --- | --- |
| `getModuleName()` | `string` | The studly-case name of the `module` argument, or of the module set with `module:use` when the argument is empty. Throws `ModuleNotFoundException` for an unknown module. |

### InteractsWithModuleRepository

| Signature | Returns | Description |
| --- | --- | --- |
| `moduleRepository()` | `RepositoryInterface` | Protected. The repository, resolved by its `modules` alias. A swap of the `Module` facade replaces it. |

### CanClearModulesCache

| Signature | Returns | Description |
| --- | --- | --- |
| `clearCache()` | `void` | Calls `resetModules()` on the repository, so the next lookup reads the `module.json` files again. It does not delete `bootstrap/cache/modules.php`. For commands. |

### ValidatesModuleArgument

Checks the `module` argument and the `--all` option before a command runs. `BaseCommand` and `module:prune` use it. The command needs an array `module` argument, an `--all` option and `InteractsWithModuleRepository`.

| Signature | Returns | Description |
| --- | --- | --- |
| `hasModulesToRun()` | `bool` | Protected. `true` when `--all` or at least one module name is given. Otherwise prints `No module given. Pass a module name or --all.` and returns `false`. |
| `validateModules()` | `bool` | Protected. `true` when every module name of the argument exists. Otherwise prints `Module [<name>] does not exist.` (or `Modules [<names>] do not exist.`) and returns `false`. |
| `moduleNamesToValidate()` | `array` | Protected. The names that `validateModules()` checks. Default: the `module` argument. |

### MigrationLoaderTrait

**Deprecated**, unused, removed in version 14. `Nwidart\Modules\Migrations\Migrator` replaces it.

| Signature | Returns | Description |
| --- | --- | --- |
| `loadMigrationFiles(string $module)` | `void` | Protected. Throws a `TypeError`: it calls `getMigrationGeneratorPath()`. |
| `getMigrationGeneratorPath()` | `string` | Protected. Returns the whole `paths.generator.migration` array, which does not match its return type: calling it throws a `TypeError`. |

## FileActivator

`Nwidart\Modules\Activators\FileActivator` implements `ActivatorInterface`. It is the activator of the `file` entry of `modules.activators`. It keeps the statuses in a JSON file: an object that maps each module name to `true` or `false`.

```json
{
    "Blog": true,
    "Shop": false
}
```

| Signature | Returns | Description |
| --- | --- | --- |
| `new FileActivator(Container $app)` | `FileActivator` | Reads the file at `modules.activators.file.statuses-file` (default `base_path('modules_statuses.json')`). A missing or empty file gives no statuses. Throws [`InvalidJsonException`](/laravel-modules/reference/api/exceptions/#invalidjsonexception) for a file that is not valid JSON or is a non-empty JSON list. |
| `getStatusesFilePath()` | `string` | The path of the statuses file. |
| `getStatuses()` | `array<string, bool>` | The stored statuses, keyed by module name. They are the ones read when the activator was created. |
| `enable(Module $module)`, `disable(Module $module)`, `setActive(Module $module, bool $active)`, `setActiveByName(string $name, bool $status)` | `void` | Set the status, then write the whole file. |
| `hasStatus(Module\|string $module, bool $status)` | `bool` | `true` when the stored status equals `$status`. A module with no stored status has the status `false`. |
| `delete(Module $module)` | `void` | Removes the entry of the module and writes the file. Does nothing when there is no entry. |
| `reset()` | `void` | Deletes the file and forgets the statuses in memory. |

The activator reads the file once, when it is created. It stores nothing for a module that was never enabled or disabled, and `module:make` writes a status for each module it creates.
