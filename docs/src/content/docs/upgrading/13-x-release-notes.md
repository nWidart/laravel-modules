---
title: Changes in 13.x
description: What changed in laravel-modules 13.0, 13.1, 13.2 and 13.3, and what you must do for each change.
---

This page lists the user-visible changes of each 13.x release, and says for each one whether you must act and on what condition. The source is the `CHANGELOG.md` of the repository and the GitHub releases. To move from version 12, see [Upgrade from 12 to 13](/laravel-modules/upgrading/upgrade-to-13/).

| Release | Date | What you may need to do |
| --- | --- | --- |
| [13.3.0](#1330) | 2026-10-28 | Fix scripts that pass an empty generator name or a seeder named `Seeder`. Merge new stub content into published stubs if you want it. |
| [13.2.0](#1320) | 2026-10-25 | Check the [list of actions](#action-may-be-required) if you published the config or stubs, changed generator paths, ran `module:publish-translation`, or run module commands in scripts, CI or deploys. |
| [13.1.0](#1310) | 2026-10-25 | Update if you use `module:install` (security fix). Add `--force` to migration commands that run non-interactively in production. |
| [13.0.0](#1300) | 2026-03-19 | Use PHP 8.3 or later. No code change. |

## 13.3.0

Released 2026-10-28. [GitHub release v13.3.0](https://github.com/nWidart/laravel-modules/releases/tag/v13.3.0). The release has no upgrade notes.

You must act in two cases:

- **A script passes an empty name to a generator, or a seeder named `Seeder`.** Generators now reject both and exit with an error ([#2246](https://github.com/nWidart/laravel-modules/pull/2246)). Pass a real name.
- **You published stubs and want the new output.** The package reads your copy, so it never changes by itself. These stubs changed between 13.2.0 and 13.3.0: `command.stub`, `config.stub`, `job.stub`, `job-queued.stub` and `model.stub`. Compare each with the copy in `src/Commands/stubs/` and merge what you want. The new stubs `job-middleware.stub` and `resource-json-api.stub` need no action: a published folder falls back to the package copy of any stub it lacks.

New:

- Stubs for commands and jobs match Laravel 13 ([#2238](https://github.com/nWidart/laravel-modules/pull/2238)).
- `module:make-resource --json-api` ([#2241](https://github.com/nWidart/laravel-modules/pull/2241)).
- `module:make-config` and `module:make-job-middleware` ([#2242](https://github.com/nWidart/laravel-modules/pull/2242)).
- `module:make-model --attributes` for attribute-style models ([#2243](https://github.com/nWidart/laravel-modules/pull/2243)).

Fixed:

- Models generated with `--factory` carry `#[UseFactory]` ([#2237](https://github.com/nWidart/laravel-modules/pull/2237)). Only newly generated models change.

Faster, with no action needed:

- A module skips its config scan on cached web requests ([#2230](https://github.com/nWidart/laravel-modules/pull/2230)).
- The package reads `module.json` once per application ([#2231](https://github.com/nWidart/laravel-modules/pull/2231)).
- Module schedules register only when `Schedule` resolves ([#2239](https://github.com/nWidart/laravel-modules/pull/2239)).

## 13.2.0

Released 2026-10-25. [GitHub release v13.2.0](https://github.com/nWidart/laravel-modules/releases/tag/v13.2.0).

None of the items below applies if you use the default generator paths, have no published stubs, always name a module in scripts, and have no module with a `--plain` or custom provider that relies on translation auto-discovery. Read the groups below if one of those conditions does not hold.

### Action may be required

Each item says on what condition you act.

- **Custom `stubs.path`.** Act if you set `stubs.path` in a published config. The package now ignores the path unless `'stubs' => ['enabled' => true]` is set, and uses its own stubs instead ([#2198](https://github.com/nWidart/laravel-modules/pull/2198)).
- **Custom `paths.generator.<key>` without `namespace`.** Act if you changed the `path` of one of `actions`, `casts`, `enums`, `exceptions`, `helpers`, `interfaces`, `repository`, `services` or `traits` and set no `namespace`. The namespace now derives from `path`. Add `namespace` to keep the old one ([#2207](https://github.com/nWidart/laravel-modules/pull/2207)).
- **Generator paths outside `paths.app_folder`.** Act if you changed `app_folder` or a generator `path`. `module:make` now exits 1 and lists the bad keys when a path is outside `paths.app_folder`, or, with `app_folder => ''`, when a path does not match its StudlyCase namespace (`Providers`, not `app/Providers`) ([#2209](https://github.com/nWidart/laravel-modules/pull/2209), [#2225](https://github.com/nWidart/laravel-modules/pull/2225)). The default config passes.
- **`generate => false` skips the stub files its key owns.** Act if you set `generate` to `false` for `assets`, `routes`, `views` or `config` in `paths.generator` and still want those files. For example, with `paths.generator.assets.generate` set to `false`, `module:make` no longer writes `vite.config.js` and `package.json`. Set the flag back to `true` to get them ([#2223](https://github.com/nWidart/laravel-modules/pull/2223)).
- **Published `route-provider.stub` and `scaffold/provider.stub`.** No action is required: old copies keep working as before. Act only to get the new behaviour (route methods dropped when the route file is not in `stubs.files` or `routes.generate` is `false`, and a provider list that follows the generated providers). Copy the `%%START_WEB_ROUTES%%`/`%%END_WEB_ROUTES%%` and `%%START_API_ROUTES%%`/`%%END_API_ROUTES%%` markers and `protected array $providers = [$PROVIDERS$];` from `src/Commands/stubs/` ([#2216](https://github.com/nWidart/laravel-modules/pull/2216), [#2180](https://github.com/nWidart/laravel-modules/pull/2180)).
- **Translation auto-discovery without a published config.** Act if you have no `config/modules.php`, a module whose provider does not extend `ModuleServiceProvider` (a `--plain` module, for example) and translations in it. Auto-discovery is now off, as the package default says. Publish the config and set `'auto-discover' => ['translations' => true]`. Modules with a generated provider load their translations through the provider and are not affected ([#2184](https://github.com/nWidart/laravel-modules/pull/2184)).
- **Published translations moved.** Act if you ran `module:publish-translation` before. It wrote to `resources/lang/{module}`, a folder the module provider never read. It now writes to `lang/modules/{module}`, and the provider reads that folder. Move the old files, and remove `resources/lang` when it is empty: while that folder exists, Laravel's `lang_path()` points to it and `lang/` stops loading. A module provider generated by an earlier version reads `resource_path('lang/modules/{module}')` in its own `registerTranslations()`. Change it to `lang_path('modules/{module}')`. Files that you put in `resources/lang/modules/{module}` by hand keep working while `resources/lang` exists ([#2185](https://github.com/nWidart/laravel-modules/pull/2185)).
- **Published module config files.** Act if you published a module config file other than `config.php` (for example `settings.php`) before. It went to `config/<file>.php`, which Laravel reads as the key `<file>`, so your edits never reached the module's key `<module>.<file>`. Files now publish to `config/<module>/<file>.php`. Delete the old copy and publish again. `config.php` still publishes to `config/<module>.php` ([#2186](https://github.com/nWidart/laravel-modules/pull/2186)).
- **`modules_statuses.json` that is not valid.** Act if the file holds invalid JSON or a non-empty JSON list. The activator now throws instead of reading it. A missing file, an empty file and `{}` still mean "no statuses". A wrong `modules.activator` or a missing activator class also throws ([#2205](https://github.com/nWidart/laravel-modules/pull/2205)).

### Behaviour changes

Each item says on what condition you act.

- **Module command without a module in a script.** Act if a script, CI job or deploy runs a module command with no module name and no `--all` in non-interactive mode. It now prints `No module given. Pass a module name or --all.` and exits 1. It did nothing and exited 0. Add `--all` or module names ([#2212](https://github.com/nWidart/laravel-modules/pull/2212)).
- **`--all` without interaction.** Act if a script or the scheduler passes `--all` together with `-n`. `--all` now selects every module in that case too, so `module:delete --all --force -n` deletes every module ([#2187](https://github.com/nWidart/laravel-modules/pull/2187)).
- **More failures exit 1.** Act if a script treats exit code 0 as success for failing seeders and nested calls, `module:lang` with missing translations, or `module:make-middleware` and `module:make-observer` when the file exists ([#2212](https://github.com/nWidart/laravel-modules/pull/2212), [#2215](https://github.com/nWidart/laravel-modules/pull/2215), [#2210](https://github.com/nWidart/laravel-modules/pull/2210)).
- **`module:make` without a name.** Act if a script runs `module:make` non-interactively without `name`. It now requires one ([#2210](https://github.com/nWidart/laravel-modules/pull/2210)).
- **`module:migrate --subpath` and `module:migrate-fresh --pretend`.** Act if you pass either. `--subpath` is now honoured, and `module:migrate-fresh --pretend` is refused ([#2212](https://github.com/nWidart/laravel-modules/pull/2212)).
- **Publishing over existing files.** Act if you re-run `module:publish-translation` or `module:publish-migration` to overwrite files. They now skip existing files. Pass `--force` to overwrite ([#2199](https://github.com/nWidart/laravel-modules/pull/2199)).
- **`module:prune` in the schedule.** Act if you schedule `module:prune`. It found no models before. It now deletes records, so check which models it reaches ([#2197](https://github.com/nWidart/laravel-modules/pull/2197)).
- **Reset and refresh with extra migration paths.** Act if a module provider calls `loadMigrationsFrom()` for extra paths. `module:migrate-reset` and `module:migrate-refresh` now roll those migrations back too, so those tables are dropped ([#2226](https://github.com/nWidart/laravel-modules/pull/2226)).
- **Rollback and reset connection.** Act if a migration sets `$connection`, or you pass `--database`, for a secondary database. `down()` now runs on that connection. Check the secondary databases ([#2179](https://github.com/nWidart/laravel-modules/pull/2179)).
- **Listeners that run twice.** Act if `bootstrap/app.php` discovers a module listener directory (`withEvents(discover: [...])`) and you also register the same listener by hand. Event discovery now finds the module listeners, so they run twice. Remove one registration and run `event:cache` again ([#2219](https://github.com/nWidart/laravel-modules/pull/2219)).
- **`aliases` in `module.json`.** Act if a `module.json` has an `aliases` object. It was ignored before. The package now registers it for enabled modules, so check for clashes with other aliases ([#2206](https://github.com/nWidart/laravel-modules/pull/2206)).

### Extending the package

Act only if you extend these classes.

- **`Installer` and `Publisher`.** Their properties are nullable: redeclare them as nullable in a subclass. `Publisher` defaults to `$force = false` ([#2181](https://github.com/nWidart/laravel-modules/pull/2181), [#2211](https://github.com/nWidart/laravel-modules/pull/2211), [#2199](https://github.com/nWidart/laravel-modules/pull/2199)).
- **`BaseCommand` subclasses that override `initialize()`.** Call `parent::initialize()` ([#2187](https://github.com/nWidart/laravel-modules/pull/2187)).
- **`GeneratorCommand` subclasses.** The base class now has a `force` option. Remove your own ([#2210](https://github.com/nWidart/laravel-modules/pull/2210)).
- **`MigrateCommand` subclasses.** The class has no constructor, `$migrator` or `$migration_list` any more ([#2226](https://github.com/nWidart/laravel-modules/pull/2226)).

### Deprecated (removed in v14)

Act if your code or config uses one of them. They still exist in 13.x, and v14 removes them: `Module::boot()`, `register()`, `registerTranslation()` and `isLoadFilesOnBoot()`; `FileRepository::boot()` and `register()` (and on `RepositoryInterface`); `ModuleEvent::BOOT` and `REGISTER`; `Traits\MigrationLoaderTrait`; the config keys `modules.register.translations` and `modules.register.files`; `ContractsServiceProvider` ([#2204](https://github.com/nWidart/laravel-modules/pull/2204), [#2196](https://github.com/nWidart/laravel-modules/pull/2196)). See [Deprecations](/laravel-modules/upgrading/deprecations/).

### New in 13.2.0

- `moduleRefreshPaths()` for hot reload of module views with Vite ([#2217](https://github.com/nWidart/laravel-modules/pull/2217)). See [Build module assets with Vite](/laravel-modules/guides/frontend/vite/).

The release also lists many bug fixes. The groups above cover those that change behaviour.

## 13.1.0

Released 2026-10-25. [GitHub release v13.1.0](https://github.com/nWidart/laravel-modules/releases/tag/v13.1.0). The release has no upgrade notes.

You must act in two cases:

- **You use `module:install`: update to 13.1.0 or later.** `module:install` no longer passes the module name, version, path or repository URL through the shell. Before, they allowed command injection from the CLI or from a committed `modules.json`. A failed install now exits with an error instead of running `module:update` ([#2174](https://github.com/nWidart/laravel-modules/pull/2174)). The `12.x` branch does not have this fix: its installer still builds a shell command line. On 12, run `module:install` only with names and URLs that you trust, or move to 13.
- **A script runs `module:migrate-fresh` or `module:migrate-rollback` in production without a prompt.** `module:migrate-fresh` now asks for confirmation in production before it wipes the database, and a rollback asks too. Pass `--force` in scripts ([#2173](https://github.com/nWidart/laravel-modules/pull/2173), [#2175](https://github.com/nWidart/laravel-modules/pull/2175)).

Other changes need no action:

- The package ships files for Laravel Boost under `.ai/laravel-modules/` ([#2156](https://github.com/nWidart/laravel-modules/pull/2156)). Laravel Boost does not read that location. See [Use the package with AI coding assistants](/laravel-modules/ecosystem/ai-assistants/).
- `module:migrate <Module>` no longer runs the migrations of the application or of another module with a similar name ([#2171](https://github.com/nWidart/laravel-modules/pull/2171)).
- `--pretend` works on `module:migrate-rollback` and `module:migrate-reset` ([#2175](https://github.com/nWidart/laravel-modules/pull/2175)).
- Module translation loading no longer misses its namespace ([#2154](https://github.com/nWidart/laravel-modules/pull/2154)).

## 13.0.0

Released 2026-03-19. [GitHub release v13.0.0](https://github.com/nWidart/laravel-modules/releases/tag/v13.0.0). The release has no upgrade notes.

The release lists two changes: Laravel 13 compatibility ([#2138](https://github.com/nWidart/laravel-modules/pull/2138)) and a preparation change ([#2146](https://github.com/nWidart/laravel-modules/pull/2146)). Between `v12.0.5` and `v13.0.0` the files in `src/` and `config/` are identical. The `composer.json` changes are:

| Entry | 12.0.5 | 13.0.0 |
| --- | --- | --- |
| `require.php` | `>=8.2` | `^8.3` |
| `require-dev` Laravel, Testbench, PHPUnit | Laravel 12, Testbench 10, PHPUnit 11 or 12 | Laravel 13, Testbench 11, PHPUnit 12 |

The only change you must act on is the PHP version: Composer refuses 13.x on PHP 8.2. The `require-dev` entries are development dependencies of the package and do not change what you install.
