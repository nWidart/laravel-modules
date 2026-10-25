# AGENTS.md

Guidance for AI coding agents working in this repository.

## Project

`nwidart/laravel-modules`: a Laravel package for splitting a large Laravel app into modules (each module is like a mini package with its own providers, routes, views, migrations). Namespace `Nwidart\Modules\` maps to `src/`. The `master` branch targets Laravel 13 / PHP ^8.3 (branch alias `13.x-dev`). Each package major version tracks one Laravel major, so fixes go to the branch matching the target Laravel version.

## Commands

```bash
composer install
composer test                       # phpunit, full suite
vendor/bin/phpunit tests/Commands/Make/ControllerMakeCommandTest.php
vendor/bin/phpunit --filter test_it_generated_correct_file_with_content
composer update-snapshots           # regenerate spatie snapshot files after intended output changes
composer lint                       # pint (preset: laravel), this is what CI enforces
```

CI (`.github/workflows/php.yml`) runs the test suite on PHP 8.3, 8.4, and 8.5. CI also runs Pint and auto-commits style fixes. Use only `composer lint` for formatting. Do not run `composer pcf`: its php-cs-fixer rules differ from Pint and cause style churn.

Commit messages must follow Conventional Commits (`fix(scope): description`, see `CONTRIBUTING.md`); CI checks every PR commit and releases are generated from them. For releases, see `RELEASING.md`.

## Architecture

### Boot flow

`LaravelModulesServiceProvider` (extends abstract `ModulesServiceProvider`) is auto-discovered. In `register()` it:

1. Binds singletons: `RepositoryInterface` (alias `modules`, implemented by `Laravel\LaravelFileRepository`), `ActivatorInterface` (class chosen from `modules.activator` / `modules.activators` config, default `FileActivator`), and `ModuleManifest`.
2. Sets the `Stub` base path to `modules.stubs.path` if that key is set, else `src/Commands/stubs`. This happens before the package config is merged, so only a published config sets the key here. A `booted()` callback then sets the path to `modules.stubs.path` again, but only when `stubs.enabled` is true.
3. Registers `ConsoleServiceProvider` (all artisan commands) and `ContractsServiceProvider`.
4. Hooks module migration paths into the `Migrator` and module lang paths into the `translator`. Config keys `modules.auto-discover.migrations` (default true) and `modules.auto-discover.translations` (default false) control this. Separately, `Module::boot()` registers each module's translations when `modules.register.translations` is true (the default). To stop module translations from loading, set both keys to false.
5. `registerModules()`: `ModuleManifest` globs `module.json` in the scan paths. It keeps only modules the activator marks enabled and sorts them by `priority`. Then it loads their `providers` through Laravel's `ProviderRepository` (cached at `bootstrap/cache/modules.php`) and includes their `files`.

Gotcha: `FileRepository::scan()` and `ModuleManifest::getModulesData()` cache results in **static** properties. The cache is bypassed only when `app()->runningUnitTests()`. Keep this in mind when debugging stale module lists.

### Core types

- `Module` (abstract) / `Laravel\Module`: one module on disk. Reads `module.json` and `composer.json` via `Json`. Handles enable/disable/delete through the activator. Fires `modules.{lowerName}.{event}` events, for example `modules.blog.enabled` (see `Constants\ModuleEvent`).
- `FileRepository` (abstract) / `Laravel\LaravelFileRepository`: finds, scans, orders, enables, and installs modules. Backs the `Module` facade and the `module()` helper (`src/helpers.php`).
- `Activators\FileActivator`: stores enabled/disabled status in a JSON statuses file. Custom activators implement `Contracts\ActivatorInterface`.
- `Support\ModuleServiceProvider`: base class for the service provider generated inside each module (stub `scaffold/provider`).
- `Lumen*` classes are legacy and kept alongside the Laravel ones.

### Commands (`src/Commands/`)

- `Actions/`, `Database/`, `Publish/`: per-module operations extend `BaseCommand`. `BaseCommand` adds a variadic `module` argument and an `--all/-a` option, prompts with a multisearch when no module is given, and calls `executeAction($name)` per module. `--all` is resolved in `initialize()`, so it also works with `--no-interaction` (`promptForMissingArguments()` runs only for interactive input). Commands with a `--direction` option get the enabled modules ordered by priority in that direction (`desc` by default for rollback and reset). Implement `Contracts\ConfirmableCommand` to get the production confirmation and `--force` behaviour.
- `Make/`: file generators extend `Make\GeneratorCommand`. A generator implements `getTemplateContents()` (usually `Stub::create('/x.stub', [...])->render()`) and `getDestinationFilePath()`. Target sub-paths and namespaces come from `config('modules.paths.generator.<key>')` via `Support\Config\GenerateConfigReader`. Class namespaces are built with the `Traits\PathNamespace` helpers.
- `module:make` uses `Generators\ModuleGenerator`, which scaffolds folders, the stub files listed in `modules.stubs.files`, `module.json`, and resources (providers, controller, seeder).
- **New commands must be added to `ConsoleServiceProvider::defaultCommands()`**. Otherwise they are not registered, and tests also read this list.

### Stubs

Stubs live in `src/Commands/stubs/` and users can publish them to `stubs/nwidart-stubs`. Placeholders use the `$UPPER_SNAKE$` syntax (`$CLASS$`, `$CLASS_NAMESPACE$`, `$LOWER_NAME$`, ...). `Stub::getContents()` uppercases each replacement key and wraps it in `$`. Changing a stub usually changes snapshot output.

### Config

`config/config.php` is merged as `modules.*`. Important sections are `paths.generator` (per-generator path, namespace, and `generate` flag), `stubs`, `scan`, `activators`, `auto-discover`, and `commands`.

## Tests

- All tests extend `Tests\BaseTestCase` (Orchestra Testbench). It points `modules.paths.modules` at `base_path('modules')` inside the testbench skeleton, enables **every** generator, and uses in-memory sqlite.
- Typical command test: `createModule('Blog')` in `setUp`, run `$this->artisan('module:make-…', [...])`, assert the file exists, then `assertMatchesSnapshot($contents)`. Delete the module in `tearDown` with `$this->app[RepositoryInterface::class]->delete('Blog')`.
- Snapshots live in `__snapshots__/` next to each test. Update them with `composer update-snapshots` only when the output change is intended, and review the diff.
- `tests/stubs/valid/` holds fixture modules. `Modules\Recipe\` is autoloaded from `tests/stubs/valid/Recipe`.

## Laravel Boost assets

`.ai/laravel-modules/` contains the guideline (`core.blade.php`) and skill (`1/skill/laravel-modules-development`) that `laravel/boost` ships to end users of this package. These files are user-facing docs, not instructions for working in this repo.
