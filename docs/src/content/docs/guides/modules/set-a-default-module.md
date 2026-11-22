---
title: Set a default module for commands
description: Store a module with module:use so generator commands need no module argument, and clear it with module:unuse.
---

To stop typing the module name on every generator command, store a default module with `module:use`.

## Set the default module

```bash
php artisan module:use Blog
```

The name is written to `storage/app/modules/modules.used`. It stays there until you clear it, so it applies to every later command in any terminal.

## Generate without the module argument

A generator command with an empty `module` argument uses the stored module.

```bash
php artisan module:make-controller PostController
```

The file is `Modules/Blog/app/Http/Controllers/PostController.php`. An explicit module still wins: `php artisan module:make-controller PostController Shop` writes into `Shop`.

## Clear the default module

`module:unuse` takes no module name. It deletes the stored module. The command accepts a name or `--all` and ignores them.

```bash
php artisan module:unuse
```

```text
Forget Using Blog Module ........................................ DONE
```

When no module is in use, the command prints `No module is in use.` and exits with status 0. The `modules.blog.unused` event fires when a module was in use.

## Check the result

After `module:unuse`, a generator without a module fails:

```bash
php artisan module:make-controller PostController
```

```text
ERROR  No module given, and no module is set with module:use. Pass the module name, or run php artisan module:use <module> first.
```

The command exits with status 1 and prints no stack trace.

While a module is in use, the same command writes into that module.

## Related

- [`module:use`](/laravel-modules/reference/commands/module-management/#moduleuse) and [`module:unuse`](/laravel-modules/reference/commands/module-management/#moduleunuse)
- [Generator commands: shared behaviour](/laravel-modules/reference/commands/generators/#shared-behaviour)
- [The used module](/laravel-modules/reference/api/repository/#the-used-module)
- [Create a module](/laravel-modules/guides/modules/create-a-module/)
