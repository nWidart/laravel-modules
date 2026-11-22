---
title: Enable and disable modules
description: Switch modules on and off with module:enable and module:disable, or from PHP code.
---

Change a module's status to turn it on or off. A disabled module stays on disk, but the package does not register its service providers or routes.

## Disable a module

```bash
php artisan module:disable Blog
```

The command prints `Disabling Blog Module, old status: Enabled`.

## Enable a module

```bash
php artisan module:enable Blog
```

## Change several modules

Pass multiple names, or use `--all` to target every module:

```bash
php artisan module:disable Blog Shop
php artisan module:enable --all
```

If any name does not exist, the command stops before changing anything. It prints an error (such as `ERROR  Module [Nope] does not exist.`) and exits with status `1`.

## Change the status from PHP code

```php
use Nwidart\Modules\Facades\Module;

Module::disable('Blog');
Module::findOrFail('Blog')->enable();
```

Check the current status with `Module::isEnabled('Blog')`.

Status changes take effect on the next HTTP request or Artisan run, because module providers register during boot.

## Where the status is stored

The default activator writes statuses to `modules_statuses.json` in the application root. Any module missing from that file counts as disabled, including modules discovered from extra scan paths or `vendor/` (see [Load modules from extra locations](/laravel-modules/guides/modules/scan-extra-locations/)).

To store statuses elsewhere, see [Store module status with a custom activator](/laravel-modules/guides/customise/custom-activator/).

## Check the result

```bash
php artisan module:list
```

Each module shows `[Enabled]` or `[Disabled]`. Add `--only=enabled` to list enabled modules only. Run `php artisan route:list --name=blog` to confirm routes disappear while `Blog` is disabled.

## Related

- [`module:enable`](/laravel-modules/reference/commands/module-management/#moduleenable), [`module:disable`](/laravel-modules/reference/commands/module-management/#moduledisable) and [`module:list`](/laravel-modules/reference/commands/module-management/#modulelist)
- [Events](/laravel-modules/reference/api/events/): `enabling`, `enabled`, `disabling` and `disabled`
- [Repository API](/laravel-modules/reference/api/repository/)
- [Expose the modules on the actuator](/laravel-modules/guides/production/actuator-modules-endpoint/): read which modules are enabled over HTTP
- [Create a module](/laravel-modules/guides/modules/create-a-module/)
- [Delete a module](/laravel-modules/guides/modules/delete-a-module/)
- [Module status and activators](/laravel-modules/concepts/status-and-activators/): why the status is not in `module.json`, and whether to commit the statuses file
