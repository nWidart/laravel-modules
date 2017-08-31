# Changelog

All Notable changes to `laravel-modules` will be documented in this file.

## Next

## 1.27.2 - 2017-08-29

### Changed

- Allow migrate-refresh command to be run without module argument
- Module name was added to the module enable and disable events

## 1.27.1 - 2017-07-31

### Changed

- Only run composer require on the module:update command if there's something to require
- Fixing lumen support

## 1.27.0 - 2017-07-19

### Added

- Laravel Lumen support

### Changed

- Update dev dependencies grumphp and phpcsfixer to latest versions
- The `make:model` command with the `-m` flag to create the associated migration is now using a current migration file name

## 1.26.0 - 2017-07-06

### Changed

- Throw an exception if asset name structure was not correct when using `{!! Module::asset() !!}`
- Create the module used file if non existent. Will provide for a better error message if module is omitted in console commands without a module:use.

## 1.25.1 - 2017-06-29

### Changed

- More flexibility to the `json()` method, while keeping the performance improvements.

## 1.25.0 - 2017-06-29

### Changed

- Improving performance by only instantiating Json class for the module.json file once
- Added support for generic git hosts

## 1.24.0 - 2017-06-12

### Changed

- Using `resource_path` to register module views
- Changed the method to load additional eloquent factory paths

## 1.23.0 - 2017-06-09

## Added

- A `--migration` flag to the `module:make-model` command to generate the migration file with a model
- Factories are now also defined in the master service providers. This is used in the `module:make` command without the `--plain` flag, or using `module:make-provider` with the `--master` flag.
- `module_path()` helper function.

### Changed

- The default location of event listeners is now in `Listeners/`, from `Events/Handlers`

## 1.22.0 - 2017-05-22

### Changed

- Fixed the `--plain` on the `make:module` command, to not include a service provider in the `module.json` file as it's not generated.
- Add command description to the `GenerateNotificationCommand`.

## 1.21.0 - 2017-05-10

### Added

- Added the `Macroable` trait to the `Module` class.

### Changed

- The `collections` method now accepts an optional parameter to get modules by status, in a laravel collection.
- Allow laravel `5.5.*` to be used.


## 1.20.0 - 2017-04-19

### Changed

- `module:update`: Copy over the scripts key to main composer.json file
- Add a `--subpath` option to migrate command
- `module:update`: Install / require all require & require-dev package at once, instead of multiple calls to composer require.
- `module:publish-config` command now uses the namespace set up in the configuration file.

## 1.19.0 - 2017-03-16

### Changed

- `module:update` command now also takes the `require-dev` key into account
- Making the `$migrations` parameter optional on `getLastBatchNumber()`

## 1.18.0 - 2017-03-13

### Changed

- The module list command (`module:list`) now returns the native module name

## 1.17.1 - 2017-03-02

### Changed

- Fixed issues with route file location in `start.php`

## 1.17.0 - 2017-02-27

### Changed

- Add checking for failure to parse module JSON

## 1.16.0 - 2017-01-24

### Added

- Support for Laravel 5.4
- Adding show method on resource controller
- Added check for cached routes to not load them multiple times

## 1.15.0 - 2017-01-12

### Added

- Module requirements (PR #117)
- Added `Macroable` trait to `Module` class (PR #116)

### Changed

- Added missing import of the `Schema` facade on migration stubs
- A default plain migration will be used if the name was not matched against a predefined structure (create, add, delete and drop)
- Add tests for all the different migration structures above
- Fix: respecting order in reverse migrations (PR #98)
- Fix: `module:reset` and `module:migrate-rollback` didn't have `--database` option (PR #88)
- Fix: `Module::asset()`, removed obsolete backslash. (PR #91)

## 1.14.0 - 2016-10-19

### Added

- `module:make-notification` command to generate a notification class

### Changed

- Usage of the `lists()` method on the laravel collection has been removed in favor of `pluck()`
- Modules can now overwrite the default migration and seed paths in the `module.json`  file

## 0.13.1 - 2016-09-09

### Changed

- Generated emails are now generated in the `Emails` folder by default

## 0.13.0 - 2016-09-08

### Changed

- Ability to set default value on the config() method of a module.
- Mail: Setting default value to config. Using that as the namespace.
- Using PSR2 for generated stubs


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
