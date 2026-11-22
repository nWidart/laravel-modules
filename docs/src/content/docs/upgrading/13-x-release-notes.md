---
title: Changes in 13.x
description: What changed in laravel-modules 13.1, 13.2 and 13.3, and what action each change can require.
---

This page lists the user-visible changes of each 13.x minor release, and the action each may need. The source is the `CHANGELOG.md` of the repository and the GitHub releases. To move from version 12, see [Upgrade from 12 to 13](/laravel-modules/upgrading/upgrade-to-13/).

| Release | Date | Action required |
| --- | --- | --- |
| [13.3.0](#1330) | 2026-10-28 | None documented |
| [13.2.0](#1320) | 2026-10-25 | Maybe: custom stubs, generator paths, scripts |
| [13.1.0](#1310) | 2026-10-25 | None documented |

## 13.3.0

Released 2026-10-28. [GitHub release v13.3.0](https://github.com/nWidart/laravel-modules/releases/tag/v13.3.0). The release has no upgrade notes.

New:

- Stubs for commands and jobs match Laravel 13 ([#2238](https://github.com/nWidart/laravel-modules/pull/2238)). A published copy of a stub does not change, because the package reads your copy.
- `module:make-resource --json-api` ([#2241](https://github.com/nWidart/laravel-modules/pull/2241)).
- `module:make-config` and `module:make-job-middleware` ([#2242](https://github.com/nWidart/laravel-modules/pull/2242)).
- `module:make-model --attributes` for attribute-style models ([#2243](https://github.com/nWidart/laravel-modules/pull/2243)).

Fixed:

- Models generated with `--factory` carry `#[UseFactory]` ([#2237](https://github.com/nWidart/laravel-modules/pull/2237)).
- Generators reject an empty name, and a seeder named `Seeder` ([#2246](https://github.com/nWidart/laravel-modules/pull/2246)). A script that passed such a name now fails.

Faster:

- A module skips its config scan on cached web requests ([#2230](https://github.com/nWidart/laravel-modules/pull/2230)).
- The package reads `module.json` once per application ([#2231](https://github.com/nWidart/laravel-modules/pull/2231)).
- Module schedules register only when `Schedule` resolves ([#2239](https://github.com/nWidart/laravel-modules/pull/2239)).

## 13.2.0

Released 2026-10-25. [GitHub release v13.2.0](https://github.com/nWidart/laravel-modules/releases/tag/v13.2.0).

Most applications on the default published config need no change. Read the items below if you use custom stubs, custom generator paths, or run module commands in scripts, CI or deploys.

### Action may be required

- **Custom `stubs.path` needs `'stubs' => ['enabled' => true]`.** Without it the path is ignored ([#2198](https://github.com/nWidart/laravel-modules/pull/2198)).
- **Custom `paths.generator.<key>` without `namespace`** (`actions`, `casts`, `enums`, `exceptions`, `helpers`, `interfaces`, `repository`, `services`, `traits`) now derives the namespace from `path`. Add `namespace` to keep the old one ([#2207](https://github.com/nWidart/laravel-modules/pull/2207)).
- **`module:make` rejects generator paths outside `paths.app_folder`**, and with `app_folder => ''` paths that do not match their StudlyCase namespace (`Providers`, not `app/Providers`). It exits 1 and lists the bad keys ([#2209](https://github.com/nWidart/laravel-modules/pull/2209), [#2225](https://github.com/nWidart/laravel-modules/pull/2225)).
- **`generate => false` also skips the stub files the key owns.** `'assets' => false` no longer writes `vite.config.js` and `package.json`. Set the key to `true` to get them back ([#2223](https://github.com/nWidart/laravel-modules/pull/2223)).
- **Published `route-provider.stub` and `scaffold/provider.stub`:** old copies keep working as before. To get the new behaviour (routes dropped when not in `stubs.files` or `routes.generate` is false; provider list follows generated providers), copy the `%%START_WEB_ROUTES%%`/`%%END_WEB_ROUTES%%` and `%%START_API_ROUTES%%`/`%%END_API_ROUTES%%` markers and `protected array $providers = [$PROVIDERS$];` from `src/Commands/stubs/` ([#2216](https://github.com/nWidart/laravel-modules/pull/2216), [#2180](https://github.com/nWidart/laravel-modules/pull/2180)).
- **No published `config/modules.php`: translation auto-discovery is now off.** Publish the config and set `'auto-discover' => ['translations' => true]` if you relied on it ([#2184](https://github.com/nWidart/laravel-modules/pull/2184)).
- **`module:publish-translation` writes to `lang/modules/{module}`.** Move old copies from `resources/lang/{module}`. While `resources/lang` exists, `lang_path()` points to it and `lang/` stops loading. Modules generated with v11 stubs: use `lang_path('modules/...')` instead of `resource_path('lang/modules/...')` ([#2185](https://github.com/nWidart/laravel-modules/pull/2185)).
- **Module config files other than `config.php` publish to `config/<module>/<file>.php`.** Move files published earlier ([#2186](https://github.com/nWidart/laravel-modules/pull/2186)).
- **`modules_statuses.json` must be a JSON object** (module name to `true` or `false`). A corrupt file or a bad `modules.activator` config now throws ([#2205](https://github.com/nWidart/laravel-modules/pull/2205)).

### Behaviour changes

- **Scripts, CI and deploys:** a module command with no module and no `--all` in non-interactive mode prints `No module given. Pass a module name or --all.` and exits 1 (it did nothing and exited 0). Add `--all` or module names ([#2212](https://github.com/nWidart/laravel-modules/pull/2212)).
- **`--all` now runs non-interactively** (`-n`, CI, scheduler): `module:delete --all --force -n` deletes every module ([#2187](https://github.com/nWidart/laravel-modules/pull/2187)).
- **More failures exit 1:** failing seeders and nested calls, `module:lang` with missing translations, `module:make-middleware` and `module:make-observer` when the file exists ([#2212](https://github.com/nWidart/laravel-modules/pull/2212), [#2215](https://github.com/nWidart/laravel-modules/pull/2215), [#2210](https://github.com/nWidart/laravel-modules/pull/2210)).
- **`module:make` requires `name`** when non-interactive ([#2210](https://github.com/nWidart/laravel-modules/pull/2210)).
- **`module:migrate --subpath` is now honoured.** `module:migrate-fresh --pretend` is refused ([#2212](https://github.com/nWidart/laravel-modules/pull/2212)).
- **`module:publish-translation` and `module:publish-migration` skip existing files.** Pass `--force` to overwrite ([#2199](https://github.com/nWidart/laravel-modules/pull/2199)).
- **Scheduled `module:prune` now deletes records** (it found no models before) ([#2197](https://github.com/nWidart/laravel-modules/pull/2197)).
- **Reset and refresh also roll back `loadMigrationsFrom()` paths**, so those tables are dropped too ([#2226](https://github.com/nWidart/laravel-modules/pull/2226)).
- **Rollback and reset run `down()` on the migration's connection** (`$connection`, else `--database`). Check secondary databases ([#2179](https://github.com/nWidart/laravel-modules/pull/2179)).
- **Module listeners registered by hand and found by event discovery run twice.** Remove one registration and re-run `event:cache` ([#2219](https://github.com/nWidart/laravel-modules/pull/2219)).
- **`aliases` in `module.json` are now registered.** Check for clashes ([#2206](https://github.com/nWidart/laravel-modules/pull/2206)).

### Extending the package

- `Installer` and `Publisher` properties are nullable (redeclare them as nullable); `Publisher` defaults to `$force = false` ([#2181](https://github.com/nWidart/laravel-modules/pull/2181), [#2211](https://github.com/nWidart/laravel-modules/pull/2211), [#2199](https://github.com/nWidart/laravel-modules/pull/2199)).
- `BaseCommand` subclasses that override `initialize()` must call `parent::initialize()` ([#2187](https://github.com/nWidart/laravel-modules/pull/2187)).
- `GeneratorCommand` adds a `force` option: remove your own ([#2210](https://github.com/nWidart/laravel-modules/pull/2210)).
- `MigrateCommand` has no constructor, `$migrator` or `$migration_list` ([#2226](https://github.com/nWidart/laravel-modules/pull/2226)).

### Deprecated (removed in v14)

`Module::boot()`, `register()`, `registerTranslation()`, `isLoadFilesOnBoot()`; `FileRepository::boot()` and `register()` (and on `RepositoryInterface`); `ModuleEvent::BOOT` and `REGISTER`; `Traits\MigrationLoaderTrait`; config keys `modules.register.translations` and `modules.register.files`; `ContractsServiceProvider` ([#2204](https://github.com/nWidart/laravel-modules/pull/2204), [#2196](https://github.com/nWidart/laravel-modules/pull/2196)). See [Deprecations](/laravel-modules/upgrading/deprecations/).

### New in 13.2.0

- `moduleRefreshPaths()` for hot reload of module views with Vite ([#2217](https://github.com/nWidart/laravel-modules/pull/2217)). See [Vite](/laravel-modules/guides/frontend/vite/).

The release also lists many bug fixes. The notes above cover those that change behaviour.

## 13.1.0

Released 2026-10-25. [GitHub release v13.1.0](https://github.com/nWidart/laravel-modules/releases/tag/v13.1.0). The release has no upgrade notes.

- **Security:** `module:install` no longer passes the module name, version, path or repository URL through the shell, which allowed command injection from the CLI or a committed `modules.json`. A failed install now exits with an error instead of running `module:update` ([#2174](https://github.com/nWidart/laravel-modules/pull/2174)). Update to 13.1.0 or later if you use `module:install`.
- **Laravel Boost:** the package ships a core guideline and a `laravel-modules-development` skill ([#2156](https://github.com/nWidart/laravel-modules/pull/2156)). See [AI assistants](/laravel-modules/ecosystem/ai-assistants/).
- `module:migrate-fresh` asks for confirmation in production before it wipes the database ([#2173](https://github.com/nWidart/laravel-modules/pull/2173)).
- `module:migrate <Module>` no longer runs the migrations of the application or of another module with a similar name ([#2171](https://github.com/nWidart/laravel-modules/pull/2171)).
- `--pretend` works on `module:migrate-rollback` and `module:migrate-reset`, and a rollback asks for confirmation in production ([#2175](https://github.com/nWidart/laravel-modules/pull/2175)).
- Module translation loading no longer misses its namespace ([#2154](https://github.com/nWidart/laravel-modules/pull/2154)).
