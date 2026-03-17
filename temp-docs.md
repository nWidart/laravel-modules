# Inertia Support — New & Changed Features

This document covers everything added or changed to support Inertia.js across Vue, React, and Svelte.

---

## Table of Contents

1. [Configuration](#configuration)
2. [Generating an Inertia Module](#generating-an-inertia-module)
3. [Generating Inertia Pages](#generating-inertia-pages)
4. [Generating Inertia Components](#generating-inertia-components)
5. [Publishing the Inertia App Entry Point](#publishing-the-inertia-app-entry-point)
6. [Vite Config Stub](#vite-config-stub)
7. [Frontend Framework Flag Precedence](#frontend-framework-flag-precedence)
8. [Changed Files Reference](#changed-files-reference)

---

## Configuration

A new top-level `inertia` key has been added to `config/modules.php`.

```php
'inertia' => [
    'frontend' => 'vue', // Supported: "vue", "react", "svelte"
],
```

This sets the **default frontend framework** used by all Inertia-related commands when no explicit `--vue`, `--react`, or `--svelte` flag is passed. Set it once for your project and every command will pick it up automatically.

```php
// config/modules.php — switch your whole project to React:
'inertia' => [
    'frontend' => 'react',
],
```

---

## Generating an Inertia Module

### Command

```bash
php artisan module:make <ModuleName> --inertia
```

### What it generates

A standard web module **minus** the Blade views, **plus** Inertia-specific files:

| Generated | Path |
|---|---|
| Inertia controller | `app/Http/Controllers/<Name>Controller.php` |
| Index page | `resources/js/Pages/Index.{vue,jsx,svelte}` |
| Create page | `resources/js/Pages/Create.{vue,jsx,svelte}` |
| Show page | `resources/js/Pages/Show.{vue,jsx,svelte}` |
| Edit page | `resources/js/Pages/Edit.{vue,jsx,svelte}` |

Blade files (`resources/views/index.blade.php` and `resources/views/components/layouts/master.blade.php`) are **skipped**.

The controller stub (`controller-inertia.stub`) returns `Inertia::render()` responses for each resource action, pre-wired to the module's page paths.

### Frontend selection

The pages are generated using the frontend set in `config/modules.php → inertia.frontend`. There is no per-command override on `module:make` — configure the default in config before running.

### Example

```bash
# With config set to 'vue' (default):
php artisan module:make Blog --inertia
# Generates Blog/resources/js/Pages/{Index,Create,Show,Edit}.vue

# With config set to 'react':
php artisan module:make Blog --inertia
# Generates Blog/resources/js/Pages/{Index,Create,Show,Edit}.jsx

# With config set to 'svelte':
php artisan module:make Blog --inertia
# Generates Blog/resources/js/Pages/{Index,Create,Show,Edit}.svelte
```

---

## Generating Inertia Pages

### Command

```bash
php artisan module:make-inertia-page <Name> <Module> [--vue] [--react] [--svelte]
```

### Arguments & options

| | Description |
|---|---|
| `name` | Page name. Supports subdirectories: `Contacts/Index` |
| `module` | The module to generate the page in |
| `--vue` | Force Vue output |
| `--react` | Force React output |
| `--svelte` | Force Svelte output |
| *(none)* | Falls back to `config('modules.inertia.frontend')` |

### Output location

`<ModulePath>/resources/js/Pages/<Name>.{vue,jsx,svelte}`

The path respects `config('modules.paths.generator.inertia.path')` if customised.

### Examples

```bash
# Uses config default
php artisan module:make-inertia-page Index Blog

# Explicit framework override
php artisan module:make-inertia-page Index Blog --react

# Subdirectory
php artisan module:make-inertia-page Contacts/Index Blog
# → resources/js/Pages/Contacts/Index.vue

# Studly-cases the name automatically
php artisan module:make-inertia-page my-dashboard Blog
# → resources/js/Pages/MyDashboard.vue
```

### Stubs

| Framework | Stub file |
|---|---|
| Vue | `src/Commands/stubs/inertia/page-vue.stub` |
| React | `src/Commands/stubs/inertia/page-react.stub` |
| Svelte | `src/Commands/stubs/inertia/page-svelte.stub` *(new)* |

**Vue page stub** — uses `<script setup>` and `@inertiajs/vue3` `<Head>`.

**React page stub** — named export with `@inertiajs/react` `<Head>`.

**Svelte page stub** — uses `<svelte:head>` and imports `page` from `@inertiajs/svelte`.

---

## Generating Inertia Components

> New command — puts the previously-unused `inertia-components` config key to use.

### Command

```bash
php artisan module:make-inertia-component <Name> <Module> [--vue] [--react] [--svelte]
```

### Arguments & options

| | Description |
|---|---|
| `name` | Component name. Supports subdirectories: `UI/Button` |
| `module` | The module to generate the component in |
| `--vue` | Force Vue output |
| `--react` | Force React output |
| `--svelte` | Force Svelte output |
| *(none)* | Falls back to `config('modules.inertia.frontend')` |

### Output location

`<ModulePath>/resources/js/Components/<Name>.{vue,jsx,svelte}`

The path respects `config('modules.paths.generator.inertia-components.path')` if customised.

### Examples

```bash
php artisan module:make-inertia-component Button Blog
# → resources/js/Components/Button.vue

php artisan module:make-inertia-component UI/Modal Blog --react
# → resources/js/Components/UI/Modal.jsx

php artisan module:make-inertia-component DataTable Blog --svelte
# → resources/js/Components/DataTable.svelte
```

### Stubs

| Framework | Stub file |
|---|---|
| Vue | `src/Commands/stubs/inertia/component-vue.stub` *(new)* |
| React | `src/Commands/stubs/inertia/component-react.stub` *(new)* |
| Svelte | `src/Commands/stubs/inertia/component-svelte.stub` *(new)* |

Components are intentionally minimal — no `<Head>` or Inertia-specific imports, just a plain component shell ready to build on.

### Config keys used

```php
// config/modules.php
'paths' => [
    'generator' => [
        'inertia'            => ['path' => 'resources/js/Pages',      'generate' => false],
        'inertia-components' => ['path' => 'resources/js/Components', 'generate' => false],
    ],
],
```

Both `path` values can be customised. Set `generate` to `true` if you want the directory created upfront when scaffolding a module.

---

## Publishing the Inertia App Entry Point

### Command

```bash
php artisan module:publish-inertia [--vue] [--react] [--svelte] [--force|-f]
```

Publishes a pre-configured `resources/js/app.js` that resolves Inertia pages from **both** the main `resources/js/Pages` folder and every module's `resources/js/Pages` folder.

### Options

| Option | Description |
|---|---|
| `--vue` | Publish the Vue version |
| `--react` | Publish the React version |
| `--svelte` | Publish the Svelte version *(new)* |
| *(none)* | Falls back to `config('modules.inertia.frontend')` |
| `--force` / `-f` | Overwrite an existing `app.js` |

### Examples

```bash
# Publish using config default
php artisan module:publish-inertia

# Explicit framework
php artisan module:publish-inertia --svelte

# Overwrite existing file
php artisan module:publish-inertia --force
```

### What the published file does

The published `app.js` uses `import.meta.glob` to resolve pages from two locations:

1. `./Pages/**/*.<ext>` — the application's own pages
2. `/Modules/*/resources/js/Pages/**/*.<ext>` — every module's pages

Page names are expected in the format `ModuleName/PageName` (e.g. `Blog/Index`). If the module glob matches, it loads from there; otherwise it falls back to the app pages glob.

### Stubs

| Framework | Stub file |
|---|---|
| Vue | `src/Commands/stubs/inertia/app-vue.stub` |
| React | `src/Commands/stubs/inertia/app-react.stub` |
| Svelte | `src/Commands/stubs/inertia/app-svelte.stub` *(new)* |

---

## Vite Config Stub

The per-module `vite.config.js` stub (`src/Commands/stubs/vite.stub`) now includes commented-out entries for all three frameworks:

```js
// Uncomment the import for your frontend framework:
// import vue from '@vitejs/plugin-vue';
// import react from '@vitejs/plugin-react';
// import { svelte } from '@sveltejs/vite-plugin-svelte';   ← new

// ...plugins array:
// vue({ template: { transformAssetUrls: { base: null, includeAbsolute: false } } }),
// react(),
// svelte(),   ← new
```

Uncomment the relevant lines for whichever framework you are using.

---

## Frontend Framework Flag Precedence

All Inertia commands use the same resolution order:

1. `--react` flag → React
2. `--svelte` flag → Svelte
3. `--vue` flag → Vue
4. `config('modules.inertia.frontend')` → whatever is set in config
5. Hardcoded fallback → `'vue'` (if config key is missing)

This means **flags always win** over config — useful when you need to generate a one-off page in a different framework from your project default.

---

## Changed Files Reference

### New files

| File | Description |
|---|---|
| `src/Commands/Make/InertiaComponentMakeCommand.php` | New `module:make-inertia-component` command |
| `src/Commands/stubs/inertia/page-svelte.stub` | Svelte page template |
| `src/Commands/stubs/inertia/app-svelte.stub` | Svelte Inertia app entry point |
| `src/Commands/stubs/inertia/component-vue.stub` | Vue component template |
| `src/Commands/stubs/inertia/component-react.stub` | React component template |
| `src/Commands/stubs/inertia/component-svelte.stub` | Svelte component template |

### Modified files

| File | What changed |
|---|---|
| `config/config.php` | Added `inertia.frontend` config key |
| `src/Commands/Make/InertiaPageMakeCommand.php` | Added `--svelte` option; added `getInertiaFrontend()` for config-based default |
| `src/Commands/Publish/PublishInertiaCommand.php` | Added `--vue` and `--svelte` options; added `getInertiaFrontend()` for config-based default |
| `src/Commands/Make/ModuleMakeCommand.php` | Added `--inertia` option; calls `setInertia()` on the generator |
| `src/Generators/ModuleGenerator.php` | Added `$inertia` property + `setInertia()`; `generateFiles()` skips Blade view stubs for Inertia modules; `generateResources()` uses `--inertia` controller and generates CRUD pages |
| `src/Providers/ConsoleServiceProvider.php` | Registered `InertiaComponentMakeCommand` |
| `src/Commands/stubs/vite.stub` | Added Svelte import and plugin comments |
