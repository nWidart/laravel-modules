---
title: Version compatibility
description: Which version of laravel-modules works with which version of Laravel and PHP, and which versions are maintained.
---

Each major version of `nwidart/laravel-modules` tracks one major version of Laravel.

## Versions

| Laravel | laravel-modules | PHP | Branch | Latest release |
| --- | --- | --- | --- | --- |
| 13 | `^13.0` | `^8.3` | `master` | 13.3.0 |
| 12 | `^12.0` | `>=8.2` | `12.x` | 12.0.5 |
| 11 | `^11.0` | `>=8.2` | none | 11.1.10 |
| 10 | `^10.0` | `>=8.1` | none | 10.0.6 |

The PHP column reflects `require.php` in `composer.json` at tags `v13.3.0`, `v12.0.5`, `v11.1.10`, and `10.0.6`. The package does not constrain `laravel/framework` in `composer.json`. The table lists the Laravel version each major targets and tests against.

Older Laravel versions map as follows:

| Laravel | 5.4 | 5.5 | 5.6 | 5.7 | 5.8 | 6 | 7 | 8 | 9 |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| laravel-modules | `^1.0` | `^2.0` | `^3.0` | `^4.0` | `^5.0` | `^6.0` | `^7.0` | `^8.0` | `^9.0` |

Version 13.x requires the `dom`, `json`, and `simplexml` PHP extensions, plus `wikimedia/composer-merge-plugin` `^2.1`.

Lumen is unsupported. The Lumen classes are deprecated in 13.x and removed in v14; `LumenModulesServiceProvider` cannot register since 11.1.0. See [Deprecations](/laravel-modules/upgrading/deprecations/).

## Support policy

- `master` is the current major version. New features and fixes land there first.
- `12.x` is the maintenance branch for version 12. It receives security fixes only, with no bug fixes or backports.
- Versions 11 and older are unmaintained and have no maintenance branch.

To move between major versions, see [Upgrade from 12 to 13](/laravel-modules/upgrading/upgrade-to-13/).
