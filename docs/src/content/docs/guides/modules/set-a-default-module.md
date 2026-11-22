---
title: Set a default module for commands
description: Store a module with module:use so generator commands need no module argument, and clear it with module:unuse.
---

Store a default module with `module:use` so you do not have to pass the module name to every generator command.

## Set the default module

```bash
php artisan module:use Blog
```

This writes the module name to `storage/app/modules/modules.used`. Every subsequent generator command in any terminal uses `Blog` until you clear it.

## Generate without the module argument

Omit the `module` argument on any generator command to target the stored module:

```bash
php artisan module:make-controller PostController
```

This creates `Modules/Blog/app/Http/Controllers/PostController.php`. Passing an explicit module name overrides the default (for example `php artisan module:make-controller PostController Shop` writes into `Shop`).

## Clear the default module

Run `module:unuse` to clear the stored module (any module name or `--all` flag passed to it is ignored):

```bash
php artisan module:unuse
```

```text
Forget Using Blog Module ........................................ DONE
```

If no module is currently set, `module:unuse` prints `No module is in use.` and exits with status `0`. When a module was in use, it fires `modules.blog.unused`.

## Check the result

After `module:unuse`, running a generator without a module name fails with exit status `1`:

```bash
php artisan module:make-controller PostController
```

```text
ERROR  No module given, and no module is set with module:use. Pass the module name, or run php artisan module:use <module> first.
```

## Related

- [`module:use`](/laravel-modules/reference/commands/module-management/#moduleuse) and [`module:unuse`](/laravel-modules/reference/commands/module-management/#moduleunuse)
- [Generator commands: shared behaviour](/laravel-modules/reference/commands/generators/#shared-behaviour)
- [The used module](/laravel-modules/reference/api/repository/#the-used-module)
- [Create a module](/laravel-modules/guides/modules/create-a-module/)
