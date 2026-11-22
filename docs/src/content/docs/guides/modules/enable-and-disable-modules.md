---
title: Enable and disable modules
description: Switch modules on and off with module:enable and module:disable, or from PHP code.
---

To turn a module on or off, change its status. A disabled module stays on disk, but the package does not register its service providers, so its routes are gone.

## Disable a module

```bash
php artisan module:disable Blog
```

The command prints the previous status: `Disabling Blog Module, old status: Enabled`.

## Enable a module

```bash
php artisan module:enable Blog
```

## Change several modules

To change several modules, list the names. To change every module, use `--all`.

```bash
php artisan module:disable Blog Shop
php artisan module:enable --all
```

An unknown name stops the command before it changes any module. The command prints one error line and exits with status 1, with no stack trace:

```text
ERROR  Module [Nope] does not exist.
```

With several unknown names, the line is `Modules [Nope, Typo] do not exist.`

## Change the status from PHP code

```php
use Nwidart\Modules\Facades\Module;

Module::disable('Blog');
Module::findOrFail('Blog')->enable();
```

`Module::isEnabled('Blog')` returns the current status.

A change applies from the next request or Artisan run: the package registers the providers of enabled modules when the application boots.

## Where the status is stored

The default activator writes the statuses to `modules_statuses.json` in the application root. A module with no entry in that file counts as disabled. This includes a module the package finds by scanning an extra location or `vendor/`: it stays disabled until you enable it. See [Load modules from extra locations](/laravel-modules/guides/modules/scan-extra-locations/). To store the status somewhere else, see [Store module status with a custom activator](/laravel-modules/guides/customise/custom-activator/).

## Check the result

```bash
php artisan module:list
```

The module shows `[Disabled]` or `[Enabled]`. To list the enabled modules only, add `--only=enabled`. To see the effect of a web module, run `php artisan route:list --name=blog`: it lists no route while `Blog` is disabled.

## Related

- [`module:enable`](/laravel-modules/reference/commands/module-management/#moduleenable), [`module:disable`](/laravel-modules/reference/commands/module-management/#moduledisable) and [`module:list`](/laravel-modules/reference/commands/module-management/#modulelist)
- [Events](/laravel-modules/reference/api/events/): `enabling`, `enabled`, `disabling` and `disabled`
- [Repository API](/laravel-modules/reference/api/repository/)
- [Expose the modules on the actuator](/laravel-modules/guides/production/actuator-modules-endpoint/): read which modules are enabled over HTTP
- [Create a module](/laravel-modules/guides/modules/create-a-module/)
- [Delete a module](/laravel-modules/guides/modules/delete-a-module/)
- [Module status and activators](/laravel-modules/concepts/status-and-activators/): why the status is not in `module.json`, and whether to commit the statuses file
