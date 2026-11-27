# AGENTS.md

Guidance for AI coding agents working in this repository.

## Project

`nwidart/laravel-modules`: a Laravel package for splitting a large Laravel app into modules (each module is like a mini package with its own providers, routes, views, migrations). Namespace `Nwidart\Modules\` maps to `src/`. The `master` branch targets Laravel 13 / PHP ^8.3 (branch alias `13.x-dev`). Each package major version tracks one Laravel major, so fixes go to the branch matching the target Laravel version.

## Commands

```bash
composer install
composer check                      # lint:check + analyse + test, the same checks CI runs
composer test                       # phpunit, full suite, no coverage
vendor/bin/phpunit tests/Commands/Make/ControllerMakeCommandTest.php
vendor/bin/phpunit --filter test_it_generated_correct_file_with_content
composer update-snapshots           # regenerate spatie snapshot files after intended output changes
composer update-snapshots -- tests/Commands/Make/ControllerMakeCommandTest.php   # one test path only
composer lint                       # pint (preset: laravel), fixes files
composer lint:check                 # pint --test, reports without changing files
composer analyse                    # phpstan + Larastan (phpstan.neon.dist, level 6, src/)
composer test-coverage              # HTML report in build/coverage; needs Xdebug or PCOV
composer bench                      # benchmark this checkout against its base as the module count grows (options after --, e.g. composer bench -- --full)
```

PHPStan has no baseline. Lumen is not installed in dev, so `phpstan.neon.dist` ignores `class.notFound` for `Laravel\Lumen\*`. The Lumen classes are deprecated and v14 removes them and this ignore. PHPStan analyses for PHP 8.3 to 8.5 (`phpVersion` in `phpstan.neon.dist`), not for the local PHP, so local results match CI. Correct vendor types in `phpstan/stubs/` (currently `Factory.stub`), not in ignores. `MigrationLoaderTrait` has a `trait.unused` ignore because it is deprecated and removed in v14. Fix new errors. Do not add a baseline or new `ignoreErrors` entries without maintainer approval.

CI (`.github/workflows/php.yml`) runs `composer test` on PHP 8.3, 8.4, and 8.5, and `composer analyse` on PHP 8.3. CI also runs Pint with `composer lint` and auto-commits style fixes; on pull requests from forks it runs `composer lint:check` instead. Plain test runs need no coverage setup, even with Xdebug loaded. Only `composer test-coverage` needs Xdebug or PCOV. Do not add coverage reports back to `phpunit.xml.dist`.

Commit messages must follow Conventional Commits (`fix(scope): description`, see `CONTRIBUTING.md`); CI checks every PR commit and releases are generated from them. For releases, see `RELEASING.md`.

## Git worktrees

Never use `git stash` in a git worktree of this repo. The stash list is stored once per repository and shared by every worktree. When agents work in parallel worktrees, one agent's `git stash pop` can apply another agent's changes. This has happened.

Instead:

- Compare against the base with `git diff origin/master` or `git diff <base>...HEAD`.
- Save work with a temporary commit: `git commit -m "wip"`, then `git reset --soft HEAD~1` to undo it.
- Use a separate worktree.

## Documentation site

The documentation site is the Astro and Starlight app in `docs/`. Pages are in `docs/src/content/docs/`. See `docs/README.md` to run and build it.

A change to user-facing behaviour (commands, options, config, stubs) updates the matching page under `docs/src/content/docs/` in the same PR. `npm run build` in `docs/` must pass.

## Learnings

Before exploring, read the `.trail/learnings/` files that bear on your task. They are short notes from earlier work sessions: what was decided, what was ruled out, and what to watch out for. Commit a new learning with the change it came from.

## Architecture

### Boot flow

`LaravelModulesServiceProvider` (extends abstract `ModulesServiceProvider`) is auto-discovered. In `register()` it:

1. Binds singletons: `RepositoryInterface` (alias `modules`, implemented by `Laravel\LaravelFileRepository`), `ActivatorInterface` (class chosen from `modules.activator` / `modules.activators` config, default `FileActivator`), and `ModuleManifest`.
2. Registers a `booted()` callback that sets the `Stub` base path once: `modules.stubs.path` when `stubs.enabled` is true and the path is set, else the package's `src/Commands/stubs` (via `__DIR__`). The callback runs when the application has booted (`$this->app->booted()`), after every provider has registered, so it sees the merged package config and config set later in the boot (for example Testbench's `getEnvironmentSetUp()`).
3. Registers `ConsoleServiceProvider` (all artisan commands). `ContractsServiceProvider` is deprecated, binds nothing, and is no longer registered.
4. Hooks module migration paths into the `Migrator` and module lang paths into the `translator`. Config keys `modules.auto-discover.migrations` (default true) and `modules.auto-discover.translations` (default false) control this. Module translations are loaded by each module's generated `ModuleServiceProvider::registerTranslations()`, regardless of these keys; `auto-discover.translations` only adds the package-level translator hook. `modules.register.translations` and `modules.register.files` are deprecated and have no effect: only the deprecated `Module::boot()`/`register()` read them, and the package never calls those.
5. `registerModules()`: `ModuleManifest` builds its module data from the `module.json` files the repository already read (when the bound repository extends `FileRepository` and scans the same paths), else it globs the scan paths itself. It keeps only modules the activator marks enabled and sorts them by `priority`. Then it loads their `providers` through Laravel's `ProviderRepository` (cached at `bootstrap/cache/modules.php`) and includes their `files`.

Gotcha: `FileRepository::scan()` and `ModuleManifest::getModulesData()` cache per instance, not in static properties, and the cache applies in tests too. Both are container singletons, so each app reads every `module.json` once and each test app starts fresh. After you add or remove module folders outside the repository, call `resetModules()`; `delete()`, `addLocation()`, `module:make`, `module:delete` and `module:install` clear the cache themselves. A lookup that misses rescans once if the scan paths changed since the last scan; `all()` and `count()` return the old list until `resetModules()`.

### Core types

- `Module` (abstract) / `Laravel\Module`: one module on disk. Reads `module.json` and `composer.json` via `Json`. Handles enable/disable/delete through the activator. Fires `modules.{lowerName}.{event}` events, for example `modules.blog.enabled` (see `Constants\ModuleEvent`).
- `FileRepository` (abstract) / `Laravel\LaravelFileRepository`: finds, scans, orders, enables, and installs modules. Backs the `Module` facade and the `module()` helper (`src/helpers.php`).
- `Activators\FileActivator`: stores enabled/disabled status in a JSON statuses file. Custom activators implement `Contracts\ActivatorInterface`.
- `Support\ModuleServiceProvider`: base class for the service provider generated inside each module (stub `scaffold/provider`).
- `LumenModulesServiceProvider`, `Lumen\LumenFileRepository` and `Lumen\Module` are deprecated and removed in v14. The provider cannot register since 11.1.0.

### Commands (`src/Commands/`)

- `Actions/`, `Database/`, `Publish/`: per-module operations extend `BaseCommand`. `BaseCommand` adds a variadic `module` argument and an `--all/-a` option, prompts with a multisearch when no module is given, and calls `executeAction($name)` per module. `--all` is resolved in `initialize()`, so it also works with `--no-interaction` (`promptForMissingArguments()` runs only for interactive input). The database commands get the enabled modules ordered by priority: a command with a `--direction` option orders them in that direction (`desc` by default for rollback and reset), and `module:migrate-refresh`, which overrides `getModuleNames()` with `getEnabledModuleNames('asc')`, uses ascending order. Every other command gets every module, enabled or disabled. Implement `Contracts\ConfirmableCommand` to get the production confirmation and `--force` behaviour.
- `Make/`: file generators extend `Make\GeneratorCommand`. A generator implements `getTemplateContents()` (usually `Stub::create('/x.stub', [...])->render()`) and `getDestinationFilePath()`. Target sub-paths and namespaces come from `config('modules.paths.generator.<key>')` via `Support\Config\GenerateConfigReader`. Class namespaces are built with the `Traits\PathNamespace` helpers.
- `module:make` uses `Generators\ModuleGenerator`, which scaffolds folders, the stub files listed in `modules.stubs.files`, `module.json`, and resources (providers, controller, seeder).
- **New commands must be added to `ConsoleServiceProvider::defaultCommands()`**. Otherwise they are not registered, and tests also read this list.

### Stubs

Stubs live in `src/Commands/stubs/` and users can publish them to `stubs/nwidart-stubs`. Placeholders use the `$UPPER_SNAKE$` syntax (`$CLASS$`, `$CLASS_NAMESPACE$`, `$LOWER_NAME$`, ...). `Stub::getContents()` uppercases each replacement key and wraps it in `$`. Changing a stub usually changes snapshot output.

### Config

`config/config.php` is merged as `modules.*`. Important sections are `paths.generator` (per-generator path, namespace, and `generate` flag), `stubs`, `scan`, `activators`, `auto-discover`, and `commands`.

### Benchmarks

`benchmarks/bench.php` (runner), `probe.php` (boot probe) and `weight.php` (module-weight files) build throwaway Laravel apps under `build/bench/` and compare this checkout with a base ref. `.github/workflows/benchmark.yml` runs it on a PR labelled `benchmark` or `benchmark-full`, and `benchmark-comment.yml` posts the report. Run one benchmark at a time per machine. See `CONTRIBUTING.md`.

## Tests

- All tests extend `Tests\BaseTestCase` (Orchestra Testbench). It points `modules.paths.modules` at `base_path('modules')` inside the testbench skeleton, enables **every** generator, and uses in-memory sqlite.
- Typical command test: `createModule('Blog')` in `setUp`, run `$this->artisan('module:make-…', [...])`, assert the file exists, then `assertMatchesSnapshot($contents)`. Delete the module in `tearDown` with `$this->app[RepositoryInterface::class]->delete('Blog')`.
- Snapshots live in `__snapshots__/` next to each test. Update them with `composer update-snapshots` only when the output change is intended, and review the diff.
- `tests/stubs/valid/` holds fixture modules. `Modules\Recipe\` is autoloaded from `tests/stubs/valid/Recipe`.

## Laravel Boost assets

`resources/boost/` contains the guideline (`guidelines/core.blade.php`) and skill (`skills/laravel-modules-development/SKILL.blade.php`) that `laravel/boost` ships to end users of this package. These files are user-facing docs, not instructions for working in this repo.
