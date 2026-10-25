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
composer analyse                    # phpstan (phpstan.neon.dist, level 1, src/)
composer test-coverage              # HTML report in build/coverage; needs Xdebug or PCOV
```

CI (`.github/workflows/php.yml`) runs `composer test` on PHP 8.3, 8.4, and 8.5, and `composer analyse` on PHP 8.3. CI also runs Pint with `composer lint` and auto-commits style fixes; on pull requests from forks it runs `composer lint:check` instead. Plain test runs need no coverage setup, even with Xdebug loaded. Only `composer test-coverage` needs Xdebug or PCOV. Do not add coverage reports back to `phpunit.xml.dist`.

Commit messages must follow Conventional Commits (`fix(scope): description`, see `CONTRIBUTING.md`); CI checks every PR commit and releases are generated from them. For releases, see `RELEASING.md`.

## Git worktrees

Never use `git stash` in a git worktree of this repo. The stash list is stored once per repository and shared by every worktree. When agents work in parallel worktrees, one agent's `git stash pop` can apply another agent's changes. This has happened.

Instead:

- Compare against the base with `git diff origin/master` or `git diff <base>...HEAD`.
- Save work with a temporary commit: `git commit -m "wip"`, then `git reset --soft HEAD~1` to undo it.
- Use a separate worktree.

## Code map (trail)

With trail installed, `trail build` writes a code map to `.trail/cache/`. The map is generated and local only (`.trail/.gitignore` lists it). Never commit or hand-edit it; rebuild it instead. Without trail, `.trail/cache/` does not exist: use plain grep and file reads.

- Start at `.trail/cache/INDEX.md`. One card per source file mirrors the tree: `.trail/cache/src/Module.md` covers `src/Module.php`, and the same holds for `tests/`, `config/`, and `scripts/`. Each card lists every symbol with its kind, line span (`L53-L60`), and signature.
- Grep a symbol or filename under `.trail/cache/` to find its card, then open the source only at that span.
- INDEX.md and some `--help` texts still say `graft/`, trail's old name. The map lives in `.trail/cache/`.
- Call edges live only in `.trail/cache/.graph/wiring.json`. Read them with `trail callers`, not by opening the file. `.trail/cache/.cache/` is trail's internal state; skip it.
- The graph misses calls through chained receivers: `trail callers Stub.render` lists only `Stub::__toString`, while most generators call `Stub::create(...)->render()`. Confirm a short caller list with `trail grep`.

```bash
trail ask "<task>" [--source]   # ranked file:line answer plus matching learnings; --source inlines the code
trail grep <pattern>            # every hit, grouped by enclosing symbol
trail skeleton <file>           # one file's symbols and line spans
trail callers <symbol>          # callers; --direction out for callees, --depth N|all for blast radius
trail map                       # directories, hubs, hotspots
trail build                     # rebuild .trail/cache/; --check exits 1 when the map is stale
```

Query commands refresh a stale map first; `--no-refresh` answers from the map as it is. Before exploring, read the `.trail/learnings/` files that bear on your task. Run `trail check` before you commit or open a PR.

## Architecture

### Boot flow

`LaravelModulesServiceProvider` (extends abstract `ModulesServiceProvider`) is auto-discovered. In `register()` it:

1. Binds singletons: `RepositoryInterface` (alias `modules`, implemented by `Laravel\LaravelFileRepository`), `ActivatorInterface` (class chosen from `modules.activator` / `modules.activators` config, default `FileActivator`), and `ModuleManifest`.
2. Registers a `booted()` callback that sets the `Stub` base path once: `modules.stubs.path` when `stubs.enabled` is true and the path is set, else the package's `src/Commands/stubs` (via `__DIR__`). The callback runs after every provider has registered, so it sees the merged package config and config set later in the boot (for example Testbench's `getEnvironmentSetUp()`).
3. Registers `ConsoleServiceProvider` (all artisan commands). `ContractsServiceProvider` is deprecated, binds nothing, and is no longer registered.
4. Hooks module migration paths into the `Migrator` and module lang paths into the `translator`. Config keys `modules.auto-discover.migrations` (default true) and `modules.auto-discover.translations` (default false) control this. Module translations are loaded by each module's generated `ModuleServiceProvider::registerTranslations()`, regardless of these keys; `auto-discover.translations` only adds the package-level translator hook. `modules.register.translations` and `modules.register.files` are deprecated and have no effect: only the deprecated `Module::boot()`/`register()` read them, and the package never calls those.
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
