---
title: Upgrade from 12 to 13
description: Move an application from laravel-modules 12 to 13, together with Laravel 13 and PHP 8.3.
---

Are there breaking changes? It depends on the level:

- **Version 13.0.0 itself: one, the PHP version.** It requires PHP `^8.3`, where 12 allowed `>=8.2`. Its `src/` and `config/` files are identical to `v12.0.5`. See [13.0.0](/laravel-modules/upgrading/13-x-release-notes/#1300).
- **Releases 13.1 to 13.3: yes, behaviour changes.** Composer installs the latest 13.x, not 13.0.0, so you get them with the upgrade. 13.1.0 adds production confirmations to two migration commands, 13.2.0 changes generators, stubs, publishing and exit codes, and 13.3.0 rejects some generator names. Each change says whether you must act: see [Changes in 13.x](/laravel-modules/upgrading/13-x-release-notes/).

## Steps

1. Check PHP. Version 13 requires PHP `^8.3`. PHP 8.2 no longer works.

   ```bash
   php -v
   ```

2. Change the constraints in your `composer.json`: Laravel to 13, and the package to 13. Follow the [Laravel 13 upgrade guide](https://laravel.com/docs/13.x/upgrade) for the Laravel side.

   ```json
   "require": {
       "laravel/framework": "^13.0",
       "nwidart/laravel-modules": "^13.0"
   }
   ```

3. Update both in one command.

   ```bash
   composer update laravel/framework nwidart/laravel-modules --with-all-dependencies
   ```

   Keep the `wikimedia/composer-merge-plugin` entries in `extra` and `allow-plugins` as they are. Version 13 uses the same autoload setup as 12.

4. Read [Changes in 13.x](/laravel-modules/upgrading/13-x-release-notes/) and act on the items that match your application. If you published `config/modules.php` or the stubs, see [Published config and stubs](#published-config-and-stubs).

5. Check the result.

   ```bash
   php artisan module:list
   ```

   Each module shows the status it had before the update.

### Why one update step

The order of Laravel and package does not matter to Composer. The package does not require `laravel/framework`: `v12.0.5` and `v13.x` list it under `require-dev` only, so Composer would install `v12.0.5` next to Laravel 13 without complaint. That combination is not tested: 12.x is developed against Laravel 12 and 13.x against Laravel 13. Updating both together keeps them in step.

## Published config and stubs

The published `config/modules.php` of version 12 keeps working. Between `v12.0.5` and `v13.3.0` the config has one new optional entry, `paths.generator.job-middleware` (without it, `module:make-job-middleware` uses the `jobs` path plus `Middleware`), and no key was removed or renamed. Check these 13.2.0 items against your copy:

- `stubs.path` is ignored unless `stubs.enabled` is `true`.
- A custom `app_folder` or generator `path` must agree, or `module:make` stops with an error.
- A `generate` flag set to `false` now also skips the stub files its key owns.
- The `register` keys are deprecated and have no effect.

Published stubs never change by themselves. These stubs changed between `v12.0.5` and `v13.3.0`: `command.stub`, `config.stub`, `job.stub`, `job-queued.stub`, `model.stub`, `route-provider.stub` and `scaffold/provider.stub`. Compare your copy with `src/Commands/stubs/` and merge what you want. The notes for [13.2.0](/laravel-modules/upgrading/13-x-release-notes/#action-may-be-required) and [13.3.0](/laravel-modules/upgrading/13-x-release-notes/#1330) say which change gives which behaviour.

## Deprecated code

Version 13.2.0 deprecates `Module::boot()`, `Module::register()` and related items. They still exist, and v14 removes them. Search your code for them: [Deprecations](/laravel-modules/upgrading/deprecations/).

## If something fails

- `composer update` reports a conflict: the message names the package that blocks the update. The package itself does not constrain the Laravel version. The cause is your own `composer.json`, another package that does not support Laravel 13 yet, or a platform PHP older than 8.3.
- A module does not load after the update: run `composer dump-autoload` and confirm `allow-plugins` still allows `wikimedia/composer-merge-plugin`.
- `modules_statuses.json` throws `InvalidJsonException`: since 13.2.0 the file must be a JSON object. See [Changes in 13.x](/laravel-modules/upgrading/13-x-release-notes/#action-may-be-required).

Version 12 stays on the `12.x` branch with security fixes only. See [Version compatibility](/laravel-modules/upgrading/compatibility/).
