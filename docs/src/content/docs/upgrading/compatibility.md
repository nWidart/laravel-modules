---
title: Version compatibility
description: Which version of laravel-modules works with which version of Laravel and PHP, and which versions are maintained.
---

Each major version of `nwidart/laravel-modules` tracks one major version of Laravel.

## Versions

| laravel-modules | Laravel | PHP | Branch | Latest release |
| --- | --- | --- | --- | --- |
| `^13.0` | 13 | `^8.3` | `master` | 13.3.0 |
| `^12.0` | 12 | `>=8.2` | `12.x` | 12.0.5 |
| `^11.0` | 11 | `>=8.2` | none | 11.1.10 |
| `^10.0` | 10 | `>=8.1` | none | 10.0.6 |

The PHP column is the `require.php` constraint of the `composer.json` at the tag `v13.3.0`, `v12.0.5`, `v11.1.10` and `10.0.6`. The package does not require `laravel/framework` itself. The Laravel column comes from the compatibility table in the `README.md` and from the `require-dev` of each tag.

Laravel 9 and earlier use the matching major: Laravel 5.4 uses `^1.0`, Laravel 5.5 uses `^2.0`, and so on up to Laravel 9 with `^9.0`.

The 13.x line requires the PHP extensions `dom`, `json` and `simplexml`, and `wikimedia/composer-merge-plugin` `^2.1`.

## Support policy

- `master` is the current major version. New features and fixes land there first.
- `12.x` is the maintenance branch of version 12. It receives security fixes only. It gets no performance changes, bug fixes or backports.
- Versions 11 and older are not maintained. They have no maintenance branch.

A release of an older major is cut from its maintenance branch. See `RELEASING.md` in the repository. A new major version is made by hand when a new Laravel major is supported, and the then-current major gets a maintenance branch first.

To move between majors, see [Upgrade from 12 to 13](/laravel-modules/upgrading/upgrade-to-13/).
