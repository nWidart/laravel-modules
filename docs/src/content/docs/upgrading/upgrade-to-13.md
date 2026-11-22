---
title: Upgrade from 12 to 13
description: Move an application from laravel-modules 12 to 13, together with Laravel 13 and PHP 8.3.
---

Version 13 of `nwidart/laravel-modules` supports Laravel 13 and PHP 8.3 or later. Between `v12.0.5` and `v13.0.0` the package changed only its dependency constraints. The source (`src/` and `config/`) is identical, so the move from 12 to 13 has no breaking change of its own.

The release you install is the latest 13.x, not 13.0.0. Releases 13.1 to 13.3 changed behaviour, and step 5 sends you to them.

## Steps

1. Check PHP. Version 13 requires PHP `^8.3`. PHP 8.2 no longer works.

   ```bash
   php -v
   ```

2. Upgrade Laravel to 13. Follow the [Laravel 13 upgrade guide](https://laravel.com/docs/13.x/upgrade). Do this first: the package does not require `laravel/framework` itself, and 13.x is built and tested against Laravel 13.

3. Change the version constraint in your `composer.json`:

   ```json
   "require": {
       "nwidart/laravel-modules": "^13.0"
   }
   ```

4. Update the package.

   ```bash
   composer update nwidart/laravel-modules
   ```

   Keep the `wikimedia/composer-merge-plugin` entries in `extra` and `allow-plugins` as they are. Version 13 uses the same autoload setup as 12.

5. Read [Changes in 13.x](/laravel-modules/upgrading/13-x-release-notes/). Release 13.2.0 lists changes that may need an action in your application, for example custom stubs, custom generator paths, and module commands in scripts or deploys. Most applications on the default config need no change.

6. Check the result.

   ```bash
   php artisan module:list
   ```

   Each module shows the status it had before the update.

## What changed in 13.0.0

The 13.0.0 release notes list two changes: Laravel 13 compatibility and a preparation commit. The diff of `composer.json` shows the PHP requirement going from `>=8.2` to `^8.3`, and the test dependencies moving to Laravel 13, Testbench 11 and PHPUnit 12. These are development dependencies of the package, and they do not change what you install.

## If something fails

- `composer update` reports a conflict on `laravel/framework`: your application is not on Laravel 13 yet. Do step 2 first.
- A module does not load after the update: run `composer dump-autoload` and confirm `allow-plugins` still allows `wikimedia/composer-merge-plugin`.
- The 13.2.0 notes mention a stricter `modules_statuses.json`. It must be a JSON object. A corrupt file now throws.

Version 12 stays on the `12.x` branch with security fixes only. See [Version compatibility](/laravel-modules/upgrading/compatibility/).
