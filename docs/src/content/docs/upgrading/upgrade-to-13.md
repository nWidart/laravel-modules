---
title: Upgrade from 12 to 13
description: Move an application from laravel-modules 12 to 13, together with Laravel 13 and PHP 8.3.
---

Are there breaking changes? It depends on the release:

- **Version 13.0.0:** requires PHP `^8.3` instead of `>=8.2`. Its `src/` and `config/` files match `v12.0.5`. See [13.0.0](/laravel-modules/upgrading/13-x-release-notes/#1300).
- **Releases 13.1 to 13.3 and `master`:** include behaviour changes. Composer installs the latest 13.x release, not 13.0.0, so review [Changes in 13.x](/laravel-modules/upgrading/13-x-release-notes/) before deploying.

## Steps

1. Check your PHP version. Version 13 requires PHP `^8.3`; PHP 8.2 no longer works.

   ```bash
   php -v
   ```

2. Update Laravel and `nwidart/laravel-modules` to `^13.0` in `composer.json`. Follow the [Laravel 13 upgrade guide](https://laravel.com/docs/13.x/upgrade) for application changes.

   ```json
   "require": {
       "laravel/framework": "^13.0",
       "nwidart/laravel-modules": "^13.0"
   }
   ```

3. Update both packages together:

   ```bash
   composer update laravel/framework nwidart/laravel-modules --with-all-dependencies
   ```

   Keep your `wikimedia/composer-merge-plugin` entries in `extra` and `allow-plugins`. Version 13 uses the same autoloading setup as version 12.

4. Review [Changes in 13.x](/laravel-modules/upgrading/13-x-release-notes/) and apply any items that affect your app. If you published `config/modules.php` or stubs, check [Published config and stubs](#published-config-and-stubs).

5. Verify the installation:

   ```bash
   php artisan module:list
   ```

   Each module keeps the status it had before the upgrade.

### Why update both together

The package lists `laravel/framework` under `require-dev` only, so Composer does not block installing `v12.0.5` alongside Laravel 13.

That combination is untested: 12.x targets Laravel 12 and 13.x targets Laravel 13. Updating both in one command keeps them aligned.

## Published config and stubs

A `config/modules.php` published from version 12 keeps working. No key was removed or renamed. Two optional keys were added on `master`:

- `paths.generator.job-middleware` (added in 13.3.0; falls back to the `jobs` path plus `Middleware` when omitted).
- `actuator.enabled` (added after 13.3.0; defaults to `true`, controls health indicator registration with `nwidart/laravel-actuator`).

Review these 13.2.0 config rules against your published file:

- `stubs.path` is ignored unless `stubs.enabled` is `true`.
- A custom `app_folder` and every class generator `path` must agree, or `module:make` exits with an error.
- Setting `generate` to `false` also skips the stub files owned by that key.
- The `register` keys are deprecated and have no effect.

### Behaviour updates to note on `master`

If you track `master` or upgrade once these changes release, note three updates:

- **Published config precedence:** published `config/<module>.php` files now override module defaults. Matching associative arrays merge recursively, while scalars, lists, and `[]` replace the module value as a whole.
- **Inertia v3:** Inertia stubs and `module:publish-inertia` target Inertia v3 (`script[data-page="app"]`, Svelte 5 `mount()`, and React `createElement`).
- **Vite 8:** newly generated modules use Vite 8 and `laravel-vite-plugin` `^3.1` in `package.json`, and configure per-module `hotFile` and `manifest: 'manifest.json'` in `vite.config.js`.

### Published stubs

Published stubs never update automatically. Compare your copies with `src/Commands/stubs/` and merge changes you want:

- **Changed between `v12.0.5` and `v13.3.0`:** `command.stub`, `config.stub`, `job.stub`, `job-queued.stub`, `model.stub`, `route-provider.stub`, and `scaffold/provider.stub`.
- **Changed after `v13.3.0` (`master`):** `package.stub` (Vite 8), `vite.stub` (`hotFile` and `manifest: 'manifest.json'`), `views/master.stub`, `inertia/*` and `middleware/handle-inertia-requests.stub` (Inertia v3), `scaffold/provider.stub` (`$healthIndicators`), `event-provider.stub`, `routes/web.stub`, and `replacement.stub`.
- **New stubs (need no action; missing stubs fall back to package copies):** `job-middleware.stub`, `resource-json-api.stub`, and `health-indicator.stub`.

See [13.2.0](/laravel-modules/upgrading/13-x-release-notes/#action-may-be-required), [13.3.0](/laravel-modules/upgrading/13-x-release-notes/#1330), and [Unreleased (master)](/laravel-modules/upgrading/13-x-release-notes/#unreleased-master) for details.

## Deprecated code

Version 13.2.0 deprecates `Module::boot()`, `Module::register()`, and related methods and config keys. Post-13.3.0 changes on `master` also deprecate the Lumen classes and `FileRepository::$stubPath`, `getStubPath()`, and `setStubPath()`. All remain in 13.x and are removed in v14. Search your code for them: [Deprecations](/laravel-modules/upgrading/deprecations/).

## If something fails

- `composer update` reports a conflict: check the package named in the error. The cause is usually another package without Laravel 13 support or a platform PHP constraint older than 8.3.
- A module does not load after the update: run `composer dump-autoload` and check that `allow-plugins` still allows `wikimedia/composer-merge-plugin`.
- `modules_statuses.json` throws `InvalidJsonException`: since 13.2.0, the file must hold a JSON object. See [Changes in 13.x](/laravel-modules/upgrading/13-x-release-notes/#action-may-be-required).

Version 12 stays on the `12.x` branch for security fixes only. See [Version compatibility](/laravel-modules/upgrading/compatibility/).
