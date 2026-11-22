---
title: Publish commands
description: Artisan commands and vendor:publish tags that publish module assets, configuration, translations and migrations, and the package files.
---

This page lists the commands that copy files from a module to the application, and the `vendor:publish` tags the package registers.

## Shared behaviour

`module:publish`, `module:publish-config`, `module:publish-migration` and `module:publish-translation` extend `BaseCommand`, the package base class of the commands that run one action for each selected module (see [Contracts and base classes](/laravel-modules/reference/api/contracts/#basecommand)). The behaviour below applies to all four. The full description is in [Module management commands](/laravel-modules/reference/commands/module-management/#shared-behaviour). `module:publish-inertia` does not extend `BaseCommand`; its entry says so.

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

`module:publish-migration` and `module:publish-translation` copy only the files that do not exist at the destination. For each file they skip, they print `File [<path>] already exists` with the status `SKIPPED`, then the warning `Existing files were kept. Use --force to overwrite them.` With `--force`, they overwrite the destination files. `.gitkeep` files are never copied.

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
- The files published are the ones that provider registers under the `config` tag. The [`ModuleServiceProvider`](/laravel-modules/reference/api/module-service-provider/#config-files) base class registers every PHP file of the module `config` directory, and publishes each to `config/<module name in lower case>.php` (for `config.php`) or to `config/<module>/<path>.php`.

```bash
php artisan module:publish-config Blog --force
```

## module:publish-inertia

Publishes the Inertia `app.js` entry file to `resources/js/app.js`.

```bash
php artisan module:publish-inertia [--vue] [--react] [--svelte] [-f|--force]
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
| `--force` | `-f` | `false` | Overwrite an existing `resources/js/app.js` without asking. |

**Notes**

- The framework is `--react` if given, else `--svelte`, else `--vue`, else `inertia.frontend` (default `vue`).
- Stub: `inertia/app-vue.stub`, `inertia/app-react.stub` or `inertia/app-svelte.stub`.
- The published `app.js` mounts the app when `<div id="app">` exists and the page has Inertia page data: the `data-page` attribute of the root element (Inertia v2) or a `<script data-page="app" type="application/json">` tag (Inertia v3). A page with `<div id="app">` and no page data is skipped silently.
- The Svelte stub uses `new App({ target: el, props })`, the Svelte 4 API. `@inertiajs/svelte` v3 requires Svelte 5, where that call needs Svelte's compatibility mode.
- A fresh Laravel application already has `resources/js/app.js`, so the first run meets the existing-file case. Without `--force`:
  - In an interactive terminal, the command asks `resources/js/app.js already exists. Overwrite?` (default no). Yes overwrites the file, prints `Published Inertia app.js to resources/js/app.js` and exits with `0`. No prints `Nothing was written. resources/js/app.js was left unchanged.` and exits with `1`.
  - With `--no-interaction` or without a TTY, the command prints `resources/js/app.js already exists. Use --force to overwrite.` and exits with `1`.
- With `--force`, the command overwrites the file without asking.
- On success the command prints a reminder that `vite.config.js` must include `resources/js/app.js` as an input.

```bash
php artisan module:publish-inertia --react
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

The `vite` tag publishes `vite-module-loader.js` next to the root `vite.config.js`. Its default export, `collectModuleAssetsPaths(paths, modulesPath)`, appends the `paths` export of the `vite.config.js` of each enabled module (from `modules_statuses.json`) to `paths` and returns the array. `modulesPath` is relative to the application root, which is the folder of the loader file. The file also exports `moduleRefreshPaths()`.

The tag does not overwrite an existing `vite-module-loader.js`: the command prints `File [vite-module-loader.js] already exists .. SKIPPED`. Run `php artisan vendor:publish --tag=vite --force` to replace it with the current file. The behaviour in the table below belongs to the current file.

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import collectModuleAssetsPaths from './vite-module-loader.js';

const paths = ['resources/css/app.css', 'resources/js/app.js'];
const allPaths = await collectModuleAssetsPaths(paths, 'Modules');

export default defineConfig({
    plugins: [laravel({ input: allPaths, refresh: true })],
});
```

`npm run build` from the application root then builds the module entry points into `public/build`, with manifest keys such as `Modules/Blog/resources/assets/js/app.js`.

| Module state | Result of the current loader |
| --- | --- |
| No `vite.config.js` | Skipped without a message. |
| `vite.config.js` exports no `paths` | Skipped without a message. |
| `vite.config.js` fails to load | Prints `Skipping the assets of module <Name>: could not load <path>: <message>`. The other modules still load. |

With the current loader, a `vite.config.js` generated by an earlier version has no `paths` export and fails to load. Replace it with the content of [`vite.stub`](/laravel-modules/reference/stubs/#vitestub).
