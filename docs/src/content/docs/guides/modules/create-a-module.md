---
title: Create a module
description: Generate a web, API, plain or Inertia module with module:make, create several at once, and set the author.
---

To generate a new, enabled module in `Modules/Blog`, run `module:make`. Prerequisites: the package is installed (see [Installation](/laravel-modules/start-here/installation/)) and `composer` is on your `PATH`, because `module:make` runs `composer dump-autoload`.

## Create a web module

```bash
php artisan module:make Blog
```

This creates `Modules/Blog` with its folders, `module.json`, `composer.json`, service providers, a controller, views, routes, and a seeder, then prints `Module [Blog] created successfully.`

## Choose another module type

Pass a type flag to change the generated scaffold:

- **API module (`--api`):** generates a JSON controller alongside the standard web files.

  ```bash
  php artisan module:make Blog --api
  ```

- **Plain module (`--plain` / `-p`):** creates only the folders and `module.json`, with no providers, routes, views, or `composer.json`.

  ```bash
  php artisan module:make Blog --plain
  ```

- **Inertia module (`--inertia`):** creates an Inertia controller and `Index`, `Create`, `Show`, and `Edit` pages in `resources/js/Pages`, without Blade views. The framework follows `inertia.frontend` in `config/modules.php` (default `vue`); see [Use Inertia in a module](/laravel-modules/guides/frontend/inertia/).

  ```bash
  php artisan module:make Blog --inertia
  ```

Web is the default. Passing `--web` overrides `--plain` and `--api`. Do not combine `--plain` with `--api` (the combination also falls back to a web module).

## Create several modules at once

Pass multiple names to generate several modules in one command. Every flag applies to all of them:

```bash
php artisan module:make Blog Shop --api
```

## Create a module that starts disabled

Add `--disabled` (`-d`) to leave the new module disabled until you enable it with [`module:enable`](/laravel-modules/guides/modules/enable-and-disable-modules/):

```bash
php artisan module:make Blog --disabled
```

## Set the author and vendor

Override the author and vendor written to the module's `composer.json`:

```bash
php artisan module:make Blog --author-name="Jane Doe" --author-email=jane@example.com --author-vendor=acme
```

This sets the package name to `acme/blog`. To change defaults for every new module, set `MODULE_VENDOR`, `MODULE_AUTHOR_NAME`, and `MODULE_AUTHOR_EMAIL` in `.env`, or edit the `composer` section of `config/modules.php`.

## Overwrite an existing module

`module:make` stops if the module already exists. Pass `--force` to delete the existing module folder and regenerate it from scratch:

```bash
php artisan module:make Blog --force
```

## Check the result

List your modules:

```bash
php artisan module:list
```

```text
Status / Name ........................................ Path / priority
[Enabled] Blog ................................... Modules/Blog [0]
```

Modules created with `--disabled` show `[Disabled]`. For web and API modules, run `php artisan route:list --name=blog` to see the generated `blog.*` web routes (no authentication required) and `api.blog.*` API routes (protected by `auth:sanctum`).

## Related

- [`module:make`](/laravel-modules/reference/commands/generators/#modulemake) and the other [generator commands](/laravel-modules/reference/commands/generators/)
- [`composer` configuration keys](/laravel-modules/reference/configuration/#composer)
- [Enable and disable modules](/laravel-modules/guides/modules/enable-and-disable-modules/)
- [Delete a module](/laravel-modules/guides/modules/delete-a-module/)
- [Change the folder layout of modules](/laravel-modules/guides/modules/change-the-folder-layout/)
- [Anatomy of a module](/laravel-modules/concepts/module-anatomy/): what `module:make` generates and why
