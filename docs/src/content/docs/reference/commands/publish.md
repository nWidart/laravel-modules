---
title: Publish commands
description: Artisan commands and vendor:publish tags that publish module assets, configuration, translations and migrations, and the package files.
---

This page lists the commands that copy files from a module to the application, and the `vendor:publish` tags the package registers.

## Shared behaviour

`module:publish`, `module:publish-config`, `module:publish-migration`, and `module:publish-translation` extend `BaseCommand` (see [Contracts and base classes](/laravel-modules/reference/api/contracts/#basecommand)). They share the behaviour below; see [Module management commands](/laravel-modules/reference/commands/module-management/#shared-behaviour) for details. `module:publish-inertia` does not extend `BaseCommand`.

### Arguments and options

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `module` (argument) | | none | One or more module names. Optional, variadic. |
| `--all` | `-a` | `false` | Run the action for every module. |

### Module selection

- With names, the command runs the action once per name.
- With `--all`, or with `All` chosen in the interactive prompt, the command uses every module, enabled or disabled.
- With no module and no interactive input, the command prints `No module given. Pass a module name or --all.` and exits with `1`.
- An unknown module name prints `Module [Nope] does not exist.` (several names: `Modules [Nope, Typo] do not exist.`) and the command exits with `1` before any action runs.

### Exit status

An action that fails does not stop the remaining modules. The command exits with `1` if the action failed for at least one module.

### Existing files

`module:publish-migration` and `module:publish-translation` skip files that already exist at the destination. Each skipped file prints `File [<path>] already exists` (`SKIPPED`), followed by `Existing files were kept. Use --force to overwrite them.` Pass `--force` to overwrite existing files. `.gitkeep` files are never copied.

## module:publish

Copies the assets of a module to the public assets path.

```bash
php artisan module:publish [<module>...] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--all` | `-a` | `false` | Publish the assets of every module. |

**Notes**

- Source: the `assets` generator path of the module (`paths.generator.assets.path`, default `resources/assets`).
- Destination: `<paths.assets>/<module name in lower case>` (default `public/modules/<name>`).
- The command always overwrites existing files. It has no `--force` option. A module without an assets directory publishes nothing.

```bash
php artisan module:publish Blog
```

## module:publish-config

Publishes the configuration files of a module to the application `config` directory.

```bash
php artisan module:publish-config [<module>...] [-f|--force] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--force` | `-f` | `false` | Overwrite config files that already exist. |
| `--all` | `-a` | `false` | Publish the config of every module. |

**Notes**

- The command runs `vendor:publish --provider=<provider> --tag=config` for each module.
- The provider is `<modules.namespace>\<Module>\<provider namespace>\<Module>ServiceProvider`. The provider namespace is `paths.generator.provider.path` without `paths.app_folder`, with `/` replaced by `\`.
- Publishes files registered under the `config` tag by that provider. [`ModuleServiceProvider`](/laravel-modules/reference/api/module-service-provider/#config-files) registers every PHP file in the module `config` folder. `config.php` publishes to `config/<lowername>.php`; other files publish to `config/<lowername>/<path>.php`.
- Published copies override module defaults. Nested associative arrays merge key by key; lists, empty arrays `[]`, and scalars replace the module value as a whole. Missing keys keep the module value. See [Config files](/laravel-modules/reference/api/module-service-provider/#config-files).

```bash
php artisan module:publish-config Blog --force
```

## module:publish-inertia

Publishes the Inertia `app.js` entry file to `resources/js/app.js`. With `--ssr`, publishes an SSR-ready `app.js` plus `ssr.js` and `inertia-pages.js`.

```bash
php artisan module:publish-inertia [--vue] [--react] [--svelte] [--ssr] [-f|--force]
```

This command does not extend `BaseCommand`.

**Arguments**

This command has no arguments. It has no `--all` option.

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--vue` | | `false` | Publish the Vue version. |
| `--react` | | `false` | Publish the React version. |
| `--svelte` | | `false` | Publish the Svelte version. |
| `--ssr` | | `false` | Publish an SSR-ready `app.js`, `ssr.js`, and the shared page resolver `inertia-pages.js`. |
| `--force` | `-f` | `false` | Overwrite existing files without asking. |

**Notes**

- The framework comes from the flag given, else `inertia.frontend` (default `vue`). Pass at most one of `--vue`, `--react`, and `--svelte`. Passing more than one writes nothing and exits with `1`. The error lists options in vue, react, svelte order (for example `The --react and --svelte options cannot be used together. Pass only one of --vue, --react or --svelte.`). This check runs before overwrite handling and is not bypassed by `--force`.
- The stubs target Inertia v3 (`inertiajs/inertia-laravel` ^3 and `@inertiajs/vue3`, `@inertiajs/react`, or `@inertiajs/svelte` ^3). Inertia v2 is not supported.
- Stub: `inertia/app-vue.stub`, `inertia/app-react.stub`, or `inertia/app-svelte.stub`. With `--ssr`: `inertia/ssr/app.stub`, `inertia/ssr/ssr.stub`, and `inertia/ssr/inertia-pages.stub`.
- The published `app.js` mounts only when `<div id="app">` and `<script data-page="app" type="application/json">` exist (rendered by `@inertia` in `inertiajs/inertia-laravel` 3). Other pages skip silently.
- The React stub mounts with `createRoot(el).render(createElement(App, props))` from `react` and `react-dom/client` without JSX, because Vite parses JSX only in `.jsx` files. React pages and components stay `.jsx`.
- The Svelte stub uses `mount(App, { target: el, props })` from `svelte` and needs Svelte 5.
- Only an earlier Vue `app.js` stays compatible with Inertia v3. Earlier React files contain JSX and fail to build. Republish React and Svelte with `--force`.
- Without `--force`, an existing `resources/js/app.js` behaves as follows:
  - Interactive: prompts `resources/js/app.js already exists. Overwrite?` (default no). Answering yes overwrites, prints `Published Inertia app.js to resources/js/app.js`, and exits with `0`. Answering no prints `Nothing was written. resources/js/app.js was left unchanged.` and exits with `1`.
  - Non-interactive (`--no-interaction` or no TTY): prints `resources/js/app.js already exists. Use --force to overwrite.` and exits with `1`.
- With `--force`, the command overwrites the files without asking.
- On success the command prints a reminder that `vite.config.js` must include `resources/js/app.js` as an input.

**With `--ssr`**

`--ssr` writes three files to `resources/js/`. The default output without `--ssr` is unchanged.

| File | Content |
| --- | --- |
| `app.js` | Client entry. It has the same page-data guard as the default `app.js` and no `setup` callback. Without `setup`, `createInertiaApp` hydrates server-rendered pages and mounts the others, so it also works while the SSR server is stopped. |
| `ssr.js` | A top-level `createInertiaApp({ resolve: resolvePage })` call. `@inertiajs/vite` finds `resources/js/ssr.js` by name and wraps it in the SSR server. Keep the call at the top level, not inside an `if`. |
| `inertia-pages.js` | The shared resolver `resolvePage(name)`. `Blog/Posts/Index` resolves to `Modules/Blog/resources/js/Pages/Posts/Index.<ext>` first, then to `resources/js/Pages/Blog/Posts/Index.<ext>`. An unknown name throws `Inertia page not found: <name>`. |

- The framework flags and `inertia.frontend` apply as before. They change the import package (`@inertiajs/vue3`, `@inertiajs/react`, or `@inertiajs/svelte`) and the page extension (`vue`, `jsx`, or `svelte`).
- The command checks all three destinations. If any exists and `--force` is not passed, nothing is written:
  - Interactive: asks once, for example `resources/js/app.js and resources/js/ssr.js already exist. Overwrite?` (default no). Answering no prints `Nothing was written. ... were left unchanged.` and exits with `1`.
  - Non-interactive: prints `<files> already exist. Use --force to overwrite.`, naming the existing files, and exits with `1`.
- A fresh Laravel 13 application ships `resources/js/app.js`, so `--ssr` usually needs `--force` or a yes answer.
- `--force` overwrites `app.js`, `ssr.js`, and `inertia-pages.js`. Before writing anything, the command refuses a destination that is a symbolic link, a directory, or an unwritable file, and refuses when `resources/js` is not writable and a file is missing. It writes nothing and exits with `1`. `--force` does not bypass these refusals.
- On success the command prints the remaining setup steps: add `resources/js/app.js` as a Vite input, run `npm install -D @inertiajs/vite`, add `inertia()` after `laravel(...)` in `vite.config.js`, set the build script to `vite build && vite build --ssr`, and run `php artisan inertia:start-ssr` in production. See [Render Inertia pages on the server](/laravel-modules/guides/frontend/inertia/#render-inertia-pages-on-the-server).
- Running the command again without `--ssr` and with `--force` rewrites only `app.js`. It leaves `ssr.js` and `inertia-pages.js` in place.

```bash
php artisan module:publish-inertia --react
php artisan module:publish-inertia --react --ssr --force
```

## module:publish-migration

Copies the migrations of a module to the application migration path.

```bash
php artisan module:publish-migration [<module>...] [-f|--force] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--force` | `-f` | `false` | Overwrite migrations that were already published. |
| `--all` | `-a` | `false` | Publish the migrations of every module. |

**Notes**

- Source: the migration path of the module (`paths.generator.migration.path`, or `migration.path` in `module.json`).
- Destination: `paths.migration` (default `database/migrations`).
- Files keep their names.

```bash
php artisan module:publish-migration Blog
```

## module:publish-translation

Copies the language files of a module to the application `lang` directory.

```bash
php artisan module:publish-translation [<module>...] [-f|--force] [-a|--all]
```

**Arguments**

| Name | Required | Description |
| --- | --- | --- |
| `module` | no | Module names. See [Shared behaviour](#shared-behaviour). |

**Options**

| Name | Shortcut | Default | Description |
| --- | --- | --- | --- |
| `--force` | `-f` | `false` | Overwrite translations that were already published. |
| `--all` | `-a` | `false` | Publish the translations of every module. |

**Notes**

- Source: the `lang` generator path of the module (`paths.generator.lang.path`, default `lang`).
- Destination: `lang/modules/<module name in lower case>` in the application.

```bash
php artisan module:publish-translation Blog
```

## vendor:publish tags

The package service provider registers three tags for Laravel `vendor:publish`. A tag is published with `php artisan vendor:publish --tag=<tag>`.

| Tag | Source | Destination |
| --- | --- | --- |
| `config` | `config/config.php` of the package | `config/modules.php` |
| `stubs` | `src/Commands/stubs` of the package | `stubs/nwidart-stubs` |
| `vite` | `scripts/vite-module-loader.js` of the package | `vite-module-loader.js` in the application root |

**Notes**

- Each module service provider that extends [`ModuleServiceProvider`](/laravel-modules/reference/api/module-service-provider/#publish-tags) also registers a `config` tag. `vendor:publish --tag=config` without `--provider` publishes the module files together with the package file.
- Publishing the `stubs` tag does not change which stubs the generators use. See [`stubs.enabled` and `stubs.path`](/laravel-modules/reference/configuration/#stubs).

```bash
php artisan vendor:publish --tag=config
```

### vite-module-loader.js

The `vite` tag publishes `vite-module-loader.js` to the application root (next to `vite.config.js`). Existing files are skipped unless `--force` is passed. For Vite configuration steps, see [Compile module assets with Vite](/laravel-modules/guides/frontend/vite/).

**Exports**

| Export | Signature | Returns | Description |
| --- | --- | --- | --- |
| default | `async collectModuleAssetsPaths(paths, modulesPath)` | `Promise<string[]>` | Reads `modules_statuses.json` in the application root and imports `vite.config.js` for each enabled module under `modulesPath` (relative to the loader directory). Appends each module's `paths` array to `paths` and returns it. |
| named | `moduleRefreshPaths(modulesPath = 'Modules')` | `string[]` | Returns full-reload globs for every module under `modulesPath`, relative to the application root. |

**`moduleRefreshPaths()` globs**

Trailing slashes are stripped from `modulesPath` (`<base>`):

- `<base>/*/app/Livewire/**`
- `<base>/*/app/View/Components/**`
- `<base>/*/lang/**`
- `<base>/*/resources/lang/**`
- `<base>/*/resources/views/**`
- `<base>/*/routes/**`

**Module loading behaviour (`collectModuleAssetsPaths`)**

| Module state | Result |
| --- | --- |
| No `vite.config.js` | Skipped silently. |
| `vite.config.js` exports no `paths` array | Skipped silently. |
| `vite.config.js` fails to load | Logs `Skipping the assets of module <Name>: could not load <path>: <message>` via `console.warn` and continues with remaining modules. |

Older module `vite.config.js` files without a `paths` export fail to load. Replace them with [`vite.stub`](/laravel-modules/reference/stubs/#vitestub).
