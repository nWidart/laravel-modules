---
title: Changes in 13.x
description: What changed in laravel-modules 13.0, 13.1, 13.2, 13.3, and 13.4, and when you must act.
---

This page lists user-visible changes in 13.x and states when you must act. It is compiled from `CHANGELOG.md` and GitHub releases. To upgrade from version 12, see [Upgrade from 12 to 13](/laravel-modules/upgrading/upgrade-to-13/).

| Release | Date | What you may need to do |
| --- | --- | --- |
| [13.4.0](#1340) | 2026-11-23 | Check config override precedence, exit codes, module Vite configs, Inertia v3 stubs, and the `module:prune --path` removal. |
| [13.3.0](#1330) | 2026-10-28 | Fix scripts passing an empty generator name or a seeder named `Seeder`. Merge updated stubs if you published them. |
| [13.2.0](#1320) | 2026-10-25 | Review [Action may be required](#action-may-be-required-1) if you published config/stubs, customised paths, published translations, or script module commands. |
| [13.1.0](#1310) | 2026-10-25 | Update if you use `module:install` (security fix). Pass `--force` to non-interactive production migration commands. |
| [13.0.0](#1300) | 2026-03-19 | Use PHP 8.3 or later. No code changes required. |

## 13.4.0

Released 2026-11-23. [GitHub release v13.4.0](https://github.com/nWidart/laravel-modules/releases/tag/v13.4.0).

Most apps need no changes. Check each group if you published config or stubs, use module Vite builds or Inertia, or extend the package.

### Action may be required

- **Application config now overrides module config:** a published `config/<module>.php` wins over the module's own file (the module won before). Associative arrays merge recursively; lists, `[]`, and scalars replace. Review your published copies and delete stale ones ([#2275](https://github.com/nWidart/laravel-modules/pull/2275)).
- **Unknown or missing module:** commands print one error line and exit 1 instead of throwing `ModuleNotFoundException`. `module:make-model -m/-c/...` also exits 1 when a nested generator fails. Check the exit code in scripts instead of catching the exception ([#2274](https://github.com/nWidart/laravel-modules/pull/2274)).
- **`module:prune --path` is removed** (it did nothing) and now fails with `The "--path" option does not exist.` Remove it from schedules and scripts ([#2282](https://github.com/nWidart/laravel-modules/pull/2282)).
- **Module `vite.config.js` must export `paths`:** without it the root build silently skips the module's assets, and the loader warns `Skipping the assets of module X`. Run `php artisan vendor:publish --tag=vite --force`, then port `export const paths` and `import.meta.url` into each existing module's `vite.config.js` and any published `vite.stub`. `package.stub` now generates Vite 8 and `laravel-vite-plugin` `^3.1`, and `vite.stub` sets `manifest: 'manifest.json'`; set `build.manifest: 'manifest.json'` in modules that move to Vite 5 or later ([#2272](https://github.com/nWidart/laravel-modules/pull/2272), [#2281](https://github.com/nWidart/laravel-modules/pull/2281)).
- **Module dev server:** `npm run dev` in an existing module wrote `public/hot` and hijacked the app's `@vite`. `vite.stub` now sets `hotFile: storage/vite-<module>.hot` and `views/master.stub` passes `storage_path('vite-<module>.hot')` as the third argument of `module_vite()`. Add both to existing modules; with published stubs, merge `vite.stub` and `views/master.stub` together ([#2285](https://github.com/nWidart/laravel-modules/pull/2285)).
- **Inertia stubs target Inertia v3 only:** passing more than one of `--vue`, `--react`, `--svelte` exits 1. On Inertia v3 with an older published `resources/js/app.js`, re-run `module:publish-inertia --force` (it now asks before overwriting). On Inertia v2, keep your `app.js` and do not re-publish. Merge any published `inertia/*` stubs ([#2276](https://github.com/nWidart/laravel-modules/pull/2276), [#2283](https://github.com/nWidart/laravel-modules/pull/2283)).

### Behaviour changes

- **New modules' `routes/web.php`:** routes are no longer wrapped in `middleware(['auth', 'verified'])`, also with `--inertia`. Existing modules are untouched. Add the middleware yourself if you want it ([#2262](https://github.com/nWidart/laravel-modules/pull/2262)).
- **Destructive commands:** `module:migrate-fresh`, `-refresh`, `-reset`, `-rollback`, and `module:delete` each have their own prohibit flag. One shared flag no longer blocks all of them, nor third-party confirmable commands built on `BaseCommand`. Use `Module::prohibitDestructiveCommands()` and call `YourCommand::prohibit()` for your own commands ([#2264](https://github.com/nWidart/laravel-modules/pull/2264)).
- **`module:make-model --force`:** also overwrites the files it generates (controller, factory, request, resource, seeder, policy) ([#2302](https://github.com/nWidart/laravel-modules/pull/2302)).
- **Module config timing:** module config merges in `ModuleServiceProvider::register()`. A custom `registerConfig()` override now runs earlier, so do not use boot-only services in it ([#2292](https://github.com/nWidart/laravel-modules/pull/2292)).
- **`module:migrate-refresh --all`:** refreshes enabled modules only ([#2261](https://github.com/nWidart/laravel-modules/pull/2261)).
- **`module:make` options:** `--web` wins over `--plain` and `--api`, `make-migration --plain` is honoured, and test namespaces drop the app folder when the test path is under `app/` ([#2259](https://github.com/nWidart/laravel-modules/pull/2259)).
- **`module:prune`:** `--model` and `--except` resolve short names, `--except` now applies, and unknown values or modules exit 1 ([#2279](https://github.com/nWidart/laravel-modules/pull/2279)).
- **`module:seed` output:** a header line, then `INFO Module [X] seeded.` or `ERROR ... seeding failed.` Update scripts that parse it ([#2273](https://github.com/nWidart/laravel-modules/pull/2273)).
- **Actuator:** with `nwidart/laravel-actuator` installed, a `modules` health indicator can report DOWN (module enabled but missing on disk). Set `modules.actuator.enabled` to `false` to opt out. A `modules` actuator endpoint is registered (not exposed by default); rename an app endpoint that uses the id `modules` ([#2287](https://github.com/nWidart/laravel-modules/pull/2287), [#2286](https://github.com/nWidart/laravel-modules/pull/2286)).
- **Laravel Boost assets:** the guideline and skill moved to `resources/boost/`, so `boost:install` offers them ([#2267](https://github.com/nWidart/laravel-modules/pull/2267)).

### Extending the package

Act only if you subclass these classes:

- **`replacement.stub`:** generated `$this->generator->getModule()->getLowerName()`, which threw a `TypeError`. It now uses `strtolower($this->generator->getName())`. Fix a published stub and existing replacement classes. `ModuleGenerator::getModule()` returns `?RepositoryInterface`: fix your overrides ([#2257](https://github.com/nWidart/laravel-modules/pull/2257)).
- **`UnUseCommand`:** no longer extends `BaseCommand`, so `executeAction()` and `getInfo()` overrides are dead; move the logic to `handle()`. `forgetUsed()` takes no required parameter: make your repository's parameter optional. `modules.<name>.unused` now fires ([#2258](https://github.com/nWidart/laravel-modules/pull/2258)).
- **`module:install`:** `modules.json` entries may set their own `path`, and `--path` with more than one entry exits 1. `InstallCommand::install()` gains a fifth parameter `?string $path = null`: update overrides ([#2269](https://github.com/nWidart/laravel-modules/pull/2269)).
- **`validateModules()`:** the protected method returns `bool`. Update overrides ([#2274](https://github.com/nWidart/laravel-modules/pull/2274)).
- **Generators:** every generator gets `--dry-run` and `--json` (a command's own same-name options keep their meaning). In custom generators, writes that bypass `FileGenerator` still happen under `--dry-run`, and your methods named `reportError`, `enterJsonMode`, `inJsonMode`, `writeJson`, or `addGeneratorRunOptions` may clash. `FileGenerator::$overwriteFile` defaults to `false` ([#2299](https://github.com/nWidart/laravel-modules/pull/2299)).

### Deprecated (removed in v14)

Version 13.4.0 deprecates the Lumen classes (`LumenModulesServiceProvider`, `Lumen\LumenFileRepository`, `Lumen\Module`, [#2278](https://github.com/nWidart/laravel-modules/pull/2278)) and `FileRepository::getStubPath()`, `setStubPath()`, and `$stubPath`, which never had an effect ([#2260](https://github.com/nWidart/laravel-modules/pull/2260)). Use `modules.stubs.path` or `Stub::setBasePath()` instead. They remain in 13.x and are removed in v14. See [Deprecations](/laravel-modules/upgrading/deprecations/).

### New in 13.4.0

- `module:generators` lists the generators, with `--json` ([#2295](https://github.com/nWidart/laravel-modules/pull/2295)).
- `--dry-run` and `--json` on `module:make` and all generators ([#2299](https://github.com/nWidart/laravel-modules/pull/2299), [#2300](https://github.com/nWidart/laravel-modules/pull/2300)).
- `module:migrate-status --json` ([#2297](https://github.com/nWidart/laravel-modules/pull/2297)).
- `module:test` ([#2298](https://github.com/nWidart/laravel-modules/pull/2298)).
- `module:lang --json` ([#2296](https://github.com/nWidart/laravel-modules/pull/2296)).
- `nwidart/laravel-actuator` endpoint and health indicators, plus `module:make-health-indicator` ([#2286](https://github.com/nWidart/laravel-modules/pull/2286), [#2287](https://github.com/nWidart/laravel-modules/pull/2287)).

The release includes additional bug fixes; the sections above cover every behaviour change.

## 13.3.0

Released 2026-10-28. [GitHub release v13.3.0](https://github.com/nWidart/laravel-modules/releases/tag/v13.3.0).

You must act in two cases:

- **Empty generator name or a seeder named `Seeder`:** generators now reject both and exit with an error ([#2246](https://github.com/nWidart/laravel-modules/pull/2246)). Pass a valid name.
- **Published stubs:** `command.stub`, `config.stub`, `job.stub`, `job-queued.stub`, and `model.stub` changed in 13.3.0. Compare them with `src/Commands/stubs/` and merge what you want. New stubs (`job-middleware.stub`, `resource-json-api.stub`) fall back to package copies automatically.

New:

- Command and job stubs match Laravel 13 ([#2238](https://github.com/nWidart/laravel-modules/pull/2238)).
- `module:make-resource --json-api` ([#2241](https://github.com/nWidart/laravel-modules/pull/2241)).
- `module:make-config` and `module:make-job-middleware` ([#2242](https://github.com/nWidart/laravel-modules/pull/2242)).
- `module:make-model --attributes` for attribute-style models ([#2243](https://github.com/nWidart/laravel-modules/pull/2243)).

Fixed:

- Models generated with `--factory` include `#[UseFactory]` ([#2237](https://github.com/nWidart/laravel-modules/pull/2237)).

Faster, with no action needed:

- Module providers skip config scanning on cached web requests ([#2230](https://github.com/nWidart/laravel-modules/pull/2230)).
- Each `module.json` is read once per application ([#2231](https://github.com/nWidart/laravel-modules/pull/2231)).
- Module schedules register only when `Schedule` resolves ([#2239](https://github.com/nWidart/laravel-modules/pull/2239)).

## 13.2.0

Released 2026-10-25. [GitHub release v13.2.0](https://github.com/nWidart/laravel-modules/releases/tag/v13.2.0).

Skip the action items below if you use default generator paths, keep no published stubs, pass module names explicitly in scripts, and use generated module providers. Otherwise, check each group.

### Action may be required

- **Custom `stubs.path`:** the package ignores `stubs.path` unless `'stubs' => ['enabled' => true]` is set ([#2198](https://github.com/nWidart/laravel-modules/pull/2198)).
- **Custom `paths.generator.<key>` without `namespace`:** `actions`, `casts`, `enums`, `exceptions`, `helpers`, `interfaces`, `repository`, `services`, and `traits` now derive their namespace from `path`. Set `namespace` explicitly to keep an older value ([#2207](https://github.com/nWidart/laravel-modules/pull/2207)).
- **Generator paths outside `paths.app_folder`:** `module:make` exits 1 and lists invalid keys when a class generator path sits outside `app_folder`. With `app_folder => ''`, paths must match their StudlyCase namespace (`Providers`, not `app/Providers`) ([#2209](https://github.com/nWidart/laravel-modules/pull/2209), [#2225](https://github.com/nWidart/laravel-modules/pull/2225)).
- **`generate => false` skips owned stub files:** setting `generate` to `false` for `assets`, `routes`, `views`, or `config` skips their owned stub files too. For example, disabling `assets` skips `vite.config.js` and `package.json`. Set the flag back to `true` to restore them ([#2223](https://github.com/nWidart/laravel-modules/pull/2223)).
- **Published `route-provider.stub` and `scaffold/provider.stub`:** old copies keep working. To enable conditional route blocks and dynamic `$providers` lists, copy `%%START_WEB_ROUTES%%`/`%%END_WEB_ROUTES%%`, `%%START_API_ROUTES%%`/`%%END_API_ROUTES%%`, and `protected array $providers = [$PROVIDERS$];` from `src/Commands/stubs/` ([#2216](https://github.com/nWidart/laravel-modules/pull/2216), [#2180](https://github.com/nWidart/laravel-modules/pull/2180)).
- **Translation auto-discovery without published config:** auto-discovery now defaults to `false`. Modules using `ModuleServiceProvider` are unaffected. If a `--plain` or custom module needs it, publish `config/modules.php` and set `'auto-discover' => ['translations' => true]` ([#2184](https://github.com/nWidart/laravel-modules/pull/2184)).
- **Published translations moved:** `module:publish-translation` now writes to `lang/modules/{module}` instead of `resources/lang/{module}` ([#2185](https://github.com/nWidart/laravel-modules/pull/2185)).
  - Move previously published files to `lang/modules/{module}` and delete empty `resources/lang` (while `resources/lang` exists, Laravel's `lang_path()` points there instead of `lang/`).
  - In older module providers that read `resource_path('lang/modules/{module}')`, update `registerTranslations()` to `lang_path('modules/{module}')`.
- **Published module config files:** non-`config.php` files (such as `settings.php`) now publish to `config/<module>/<file>.php` instead of `config/<file>.php`. Delete stale copies and republish. `config.php` still publishes to `config/<module>.php` ([#2186](https://github.com/nWidart/laravel-modules/pull/2186)).
- **Invalid `modules_statuses.json` or activator config:** invalid JSON, non-empty JSON lists, unknown activator names, and missing activator classes now throw instead of falling back silently. Missing files, empty files, and `{}` still mean no statuses ([#2205](https://github.com/nWidart/laravel-modules/pull/2205)).

### Behaviour changes

- **Missing module in non-interactive mode:** commands without a module name or `--all` now print `No module given. Pass a module name or --all.` and exit 1 instead of exiting 0 ([#2212](https://github.com/nWidart/laravel-modules/pull/2212)).
- **`--all` with `-n`:** `--all` now selects every module in non-interactive runs too, including destructive commands like `module:delete --all --force -n` ([#2187](https://github.com/nWidart/laravel-modules/pull/2187)).
- **Failures exit 1:** failing seeders, nested command failures, missing translations in `module:lang`, and existing files in `module:make-middleware` or `module:make-observer` now return exit code 1 ([#2212](https://github.com/nWidart/laravel-modules/pull/2212), [#2215](https://github.com/nWidart/laravel-modules/pull/2215), [#2210](https://github.com/nWidart/laravel-modules/pull/2210)).
- **`module:make` without a name:** non-interactive runs now require a module name ([#2210](https://github.com/nWidart/laravel-modules/pull/2210)).
- **`module:migrate --subpath` and `module:migrate-fresh --pretend`:** `--subpath` is now honoured, and `module:migrate-fresh --pretend` is rejected ([#2212](https://github.com/nWidart/laravel-modules/pull/2212)).
- **Publishing over existing files:** `module:publish-translation` and `module:publish-migration` now skip existing files unless `--force` is passed ([#2199](https://github.com/nWidart/laravel-modules/pull/2199)).
- **`module:prune` in schedules:** now finds and prunes module models; verify which models it targets ([#2197](https://github.com/nWidart/laravel-modules/pull/2197)).
- **Reset and refresh with extra migration paths:** `module:migrate-reset` and `module:migrate-refresh` now roll back extra paths registered with `loadMigrationsFrom()` ([#2226](https://github.com/nWidart/laravel-modules/pull/2226)).
- **Rollback and reset connections:** `down()` now runs on the migration's `$connection` or `--database` option ([#2179](https://github.com/nWidart/laravel-modules/pull/2179)).
- **Duplicate listeners:** Laravel event discovery now resolves module listeners in `withEvents(discover: [...])`. Remove duplicate manual registrations and re-run `event:cache` ([#2219](https://github.com/nWidart/laravel-modules/pull/2219)).
- **`aliases` in `module.json`:** now registered for enabled modules; check for alias collisions ([#2206](https://github.com/nWidart/laravel-modules/pull/2206)).

### Extending the package

Act only if you subclass these classes:

- **`Installer` and `Publisher`:** redeclare their properties as nullable in subclasses. `Publisher` defaults to `$force = false` ([#2181](https://github.com/nWidart/laravel-modules/pull/2181), [#2211](https://github.com/nWidart/laravel-modules/pull/2211), [#2199](https://github.com/nWidart/laravel-modules/pull/2199)).
- **`BaseCommand` subclasses overriding `initialize()`:** call `parent::initialize()` ([#2187](https://github.com/nWidart/laravel-modules/pull/2187)).
- **`GeneratorCommand` subclasses:** remove any duplicate `force` option; the base class defines it ([#2210](https://github.com/nWidart/laravel-modules/pull/2210)).
- **`MigrateCommand` subclasses:** the constructor, `$migrator`, and `$migration_list` were removed ([#2226](https://github.com/nWidart/laravel-modules/pull/2226)).

### Deprecated (removed in v14)

Version 13.2.0 deprecates the unused legacy lifecycle API: `Module` and `FileRepository` boot/register methods, `ModuleEvent::BOOT` and `REGISTER`, `MigrationLoaderTrait`, `modules.register.*` config keys, and `ContractsServiceProvider` ([#2204](https://github.com/nWidart/laravel-modules/pull/2204), [#2196](https://github.com/nWidart/laravel-modules/pull/2196)). They remain in 13.x and are removed in v14. See [Deprecations](/laravel-modules/upgrading/deprecations/) for the full list and replacements.

### New in 13.2.0

- `moduleRefreshPaths()` for hot reload of module views with Vite ([#2217](https://github.com/nWidart/laravel-modules/pull/2217)). See [Build module assets with Vite](/laravel-modules/guides/frontend/vite/).

The release includes additional bug fixes; the sections above cover every behaviour change.

## 13.1.0

Released 2026-10-25. [GitHub release v13.1.0](https://github.com/nWidart/laravel-modules/releases/tag/v13.1.0).

You must act in two cases:

- **Security fix for `module:install`:** update to 13.1.0 or later. `module:install` no longer passes module names, versions, paths, or URLs through a shell, preventing command injection from CLI arguments or `modules.json`. Failed installs now exit with an error instead of running `module:update` ([#2174](https://github.com/nWidart/laravel-modules/pull/2174)). The `12.x` branch does not have this fix; only pass trusted inputs on v12 or upgrade to v13.
- **Production confirmations:** `module:migrate-fresh` and `module:migrate-rollback` now ask for confirmation in production. Pass `--force` in non-interactive scripts ([#2173](https://github.com/nWidart/laravel-modules/pull/2173), [#2175](https://github.com/nWidart/laravel-modules/pull/2175)).

Other changes needing no action:

- Initial Laravel Boost files shipped under `.ai/laravel-modules/` ([#2156](https://github.com/nWidart/laravel-modules/pull/2156)); a post-13.3.0 change moved them to `resources/boost/` ([#2267](https://github.com/nWidart/laravel-modules/pull/2267)). See [Use the package with AI coding assistants](/laravel-modules/ecosystem/ai-assistants/).
- `module:migrate <Module>` no longer runs application migrations or migrations from similarly named modules ([#2171](https://github.com/nWidart/laravel-modules/pull/2171)).
- `--pretend` works on `module:migrate-rollback` and `module:migrate-reset` ([#2175](https://github.com/nWidart/laravel-modules/pull/2175)).
- Module translation loading no longer misses its namespace ([#2154](https://github.com/nWidart/laravel-modules/pull/2154)).

## 13.0.0

Released 2026-03-19. [GitHub release v13.0.0](https://github.com/nWidart/laravel-modules/releases/tag/v13.0.0).

This release adds Laravel 13 compatibility ([#2138](https://github.com/nWidart/laravel-modules/pull/2138), [#2146](https://github.com/nWidart/laravel-modules/pull/2146)). Files in `src/` and `config/` are identical to `v12.0.5`. Only `composer.json` changed:

| Entry | 12.0.5 | 13.0.0 |
| --- | --- | --- |
| `require.php` | `>=8.2` | `^8.3` |
| `require-dev` Laravel, Testbench, PHPUnit | Laravel 12, Testbench 10, PHPUnit 11 or 12 | Laravel 13, Testbench 11, PHPUnit 12 |

The only required action is upgrading PHP to 8.3 or later.
