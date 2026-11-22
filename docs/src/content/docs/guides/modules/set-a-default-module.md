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

`module:unuse` needs a module name or `--all`, like every command that acts on modules. It clears the stored module whatever the name is.

```bash
php artisan module:unuse Blog
```

## Check the result

After `module:unuse`, a generator without a module fails:

```bash
php artisan module:make-controller PostController
```

```text
Module [] does not exist!
```

The error prints as an exception with a stack trace, not as a one-line message.

While a module is in use, the same command writes into that module.

## Related

- [`module:use`](/laravel-modules/reference/commands/module-management/#moduleuse) and [`module:unuse`](/laravel-modules/reference/commands/module-management/#moduleunuse)
- [Generator commands: shared behaviour](/laravel-modules/reference/commands/generators/#shared-behaviour)
- [The used module](/laravel-modules/reference/api/repository/#the-used-module)
- [Create a module](/laravel-modules/guides/modules/create-a-module/)
