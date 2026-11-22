---
title: Version compatibility
description: Which version of laravel-modules works with which version of Laravel and PHP, and which versions are maintained.
---

Each major version of `nwidart/laravel-modules` tracks one major version of Laravel. For the Composer constraint to use, see [Versions](/laravel-modules/start-here/installation/#versions).

## Versions

| Laravel | laravel-modules | PHP | Branch | Latest release |
| --- | --- | --- | --- | --- |
| 13 | `^13.0` | `^8.3` | `master` | 13.3.0 |
| 12 | `^12.0` | `>=8.2` | `12.x` | 12.0.5 |
| 11 | `^11.0` | `>=8.2` | none | 11.1.10 |
| 10 | `^10.0` | `>=8.1` | none | 10.0.6 |

The PHP column is the `require.php` constraint of the `composer.json` at the tags `v13.3.0`, `v12.0.5`, `v11.1.10` and `10.0.6`. The package's `composer.json` does not constrain the Laravel version. The table lists the Laravel version each major is developed and tested against.

Older Laravel versions map as follows:

| Laravel | 5.4 | 5.5 | 5.6 | 5.7 | 5.8 | 6 | 7 | 8 | 9 |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| laravel-modules | `^1.0` | `^2.0` | `^3.0` | `^4.0` | `^5.0` | `^6.0` | `^7.0` | `^8.0` | `^9.0` |

The 13.x line requires the PHP extensions `dom`, `json` and `simplexml`, and `wikimedia/composer-merge-plugin` `^2.1`.

## Support policy

- `master` is the current major version. New features and fixes land there first.
- `12.x` is the maintenance branch of version 12. It receives security fixes only. It gets no performance changes, bug fixes or backports.
- Versions 11 and older are not maintained. They have no maintenance branch.

To move between majors, see [Upgrade from 12 to 13](/laravel-modules/upgrading/upgrade-to-13/).
