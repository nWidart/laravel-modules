---
title: Create a module
description: Generate a web, API, plain or Inertia module with module:make, create several at once, and set the author.
---

To get a new, enabled module in `Modules/Blog`, run `module:make`. The package must be installed as described in [Installation](/laravel-modules/start-here/installation/). The command runs `composer dump-autoload`, so `composer` must be on the `PATH`.

## Create a web module

```bash
php artisan module:make Blog
```

The command creates `Modules/Blog` with the folders, `module.json`, `composer.json`, a service provider, a route provider, a controller, views, routes and a seeder. It ends with `Module [Blog] created successfully.`

## Choose another module type

To create an API module, add `--api`. The controller returns JSON. The views, `routes/web.php` and the other files of a web module are still written.

```bash
php artisan module:make Blog --api
```

To create a plain module, add `--plain` (`-p`). It has the folders and `module.json` only: no providers, no routes, no views and no `composer.json`.

```bash
php artisan module:make Blog --plain
```

To create an Inertia module, add `--inertia`. It has an Inertia controller and the pages `Index`, `Create`, `Show` and `Edit` in `resources/js/Pages`, and no Blade views. The page format follows `inertia.frontend` in `config/modules.php` (default `vue`). See [Use Inertia in a module](/laravel-modules/guides/frontend/inertia/).

```bash
php artisan module:make Blog --inertia
```

Do not combine `--plain` and `--api`: the module becomes a web module.

## Create several modules at once

To create more than one module, list the names. Every option applies to all of them.

```bash
php artisan module:make Blog Shop --api
```

## Create a module that starts disabled

To create the module without enabling it, add `--disabled` (`-d`). Enable it later with [`module:enable`](/laravel-modules/guides/modules/enable-and-disable-modules/).

```bash
php artisan module:make Blog --disabled
```

## Set the author and vendor

To write another author or vendor into the `composer.json` of the module, pass the author options.

```bash
php artisan module:make Blog --author-name="Jane Doe" --author-email=jane@example.com --author-vendor=acme
```

The package name becomes `acme/blog`. To set the values for every module, set `MODULE_VENDOR`, `MODULE_AUTHOR_NAME` and `MODULE_AUTHOR_EMAIL` in `.env`, or change the `composer` section of `config/modules.php`.

## If the module already exists

`module:make` stops with `Module [Blog] already exists!`. To delete the module and create it again, add `--force`. The files of the existing module are lost.

```bash
php artisan module:make Blog --force
```

## Check the result

List the modules.

```bash
php artisan module:list
```

The output has a header line and a line for each module, like this for the new one:

```text
Status / Name ........................................ Path / priority
[Enabled] Blog ................................... Modules/Blog [0]
```

A module created with `--disabled` shows `[Disabled]`. For a web or API module, `php artisan route:list --name=blog` lists its routes: for a web module, 12 routes, the `blog.*` web routes and the `api.blog.*` API routes.

## Related

- [`module:make`](/laravel-modules/reference/commands/generators/#modulemake) and the other [generator commands](/laravel-modules/reference/commands/generators/)
- [`composer` configuration keys](/laravel-modules/reference/configuration/#composer)
- [Enable and disable modules](/laravel-modules/guides/modules/enable-and-disable-modules/)
- [Delete a module](/laravel-modules/guides/modules/delete-a-module/)
- [Change the folder layout of modules](/laravel-modules/guides/modules/change-the-folder-layout/)
