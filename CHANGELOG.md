# Changelog

All Notable changes to `laravel-modules` will be documented in this file.

## Next -

## 0.13.0 - 2016-09-08

### Changed

- Ability to set default value on the config() method of a module.
- Mail: Setting default value to config. Using that as the namespace.


## 0.12.0 - 2016-09-08

### Added

- Generation of Mailable classes


## 0.11.2 - 2016-08-29

### Changed

- Using stable version of laravelcollective/html (5.3)

## 0.11.1 - 2016-08-25

### Changed

- Using stable development of laravelcollective/html


## 0.11 - 2016-08-24

### Added

- Adding `module:make-job` command to generate a job class
- Adding support for Laravel 5.3

### Changed

- Added force option to module:seed command

## 0.10 - 2016-08-10

### Added

- Experimental Laravel 5.3 support

### Changed

- Make sure the class name has `Controller` appended to it as well. Previously only the file had it suffixed.

### Removed

- Dependencies: `pingpong/support` and `pingpong/generators`

## 0.9 - 2016-07-30

### Added

- Adding a plain option to the generate controller command

### Changed

- Generate controller command now generates all resource methods

## 0.8 - 2016-07-28

### Fixed

- Module generation namespace now works with `StudlyCase` ([Issue #14](https://github.com/nWidart/laravel-modules/issues/14))
- No module namespace fix (#13)

### Changed

- Using new service provider stub for module generation too

## 0.1 - 2016-06-27

Initial release
