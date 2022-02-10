# Changelog

All Notable changes to `laravel-modules` will be documented in this file.

## Next

## 8.3 - 2022-02-10

### Fixed

- The ability to override commands via config file.
- Incorrectly placed config key
- Class name when special characters are used in the rule name
- Fix error on anonymous migration

### Changed

- Changed `View/Component` to `View/Components`
- Updated test snapshots for the MakeCommand test snapshots
- Command stub signature from `signature` to `name`
- Revert PR1171 which causes tests to fail

### Added 

- Added comands make class component and make view component
- Test against php 8.0
- Merge config in register method
- Added optional controller flag to model generator command
- Set module for make-controller command
- Added tests for generating controller in model generator command
- Added test for check if migration and controller flag are both present
- Laravel mix v6 support


## 8.2 - 2020-11-11

### Added

- New `module:make-component` command to generate laravel components

### Fixed

- Fixed bug: `Target class [Nwidart\Modules\Commands\] does not exist.`

## 8.1 - 2020-11-10

### Added

- Command management via configuration file 
- Laravel 8 Factories compatibility
- New improved way to define controller types. `--web`, `--api` and `--plain` options.
- New configuration option to make compose run in silent mode

### Changed

- New generated commands now use the `$signature` property instead of `$name`
- Fixed issue where `order` was used instead of `priority`

## 8.0 - 2020-10-03

### Added

- Laravel 8 support

### Updated

- Update `ListenerMakeCommand` to properly use the `--events` option

## 7.2.0 - 2020-07-30

### Added

- Added return statements in artisan commands. Helpful to validate if something was successfully ran. (#1026)

### Changed

- Update JsonResource namespace, using the new `Illuminate\Http\Resources\Json\JsonResource`. (#969)
- Enable command returns the status code (#978)
- Removing module service provider from composer.json stub (#996)
- Fixed custom stub path issue. Replacing a hardcoded stub path. (#1016)
- Controller return type changed to `Illuminate\Contracts\Support\Renderable`. (#1020)
- Change bigIncrements method to id (#1029)
- Adding force option for module:seed (#1030)

## 7.1.0 - 2020-04-14

### Changed

- `php artsian module:enable` (without any arguments) will enable all modules
- `php artsian module:disable` (without any arguments) will disable all modules
- Updating Laravel Mix version as well as cross-env. 

## 7.0.0 - 2020-03-26

### Added

- Laravel 7.0 support 

## 6.2.0 - 2019-11-12

### Changed

- Properly guessing the namespace from the path (in `GeneratorPath` class)
- Fixing generation of resource file if the resource has been disabled to generate
- Fix when using a custom service provider namespace, namespace is correctly referenced in module.json and compose.json
- Fix when using custom service provider namespace, module path is correctly referenced in the `RouteServiceProvider` and `ModuleServiceProvider`
- Fix when using a custom path for controllers in the controller stub

## 6.1.0 - 2019-11-01

### Added

- Added new `module:delete` command

### Changed

- Add optional path parameter to `module_path` helper (PR#861)
- The default path of the `module_statuses.json` file has been moved to the Application's base path. This is to improve its visibility and the fact that it can be committed by default.
- Throw an exception when no proper activator class was configured

## 6.0.0 - 2019-09-19

### Added

- New File Activator feature. [PR #790](https://github.com/nWidart/laravel-modules/pull/790) from @ryssbowh
    
    This feature changes how modules are activated and de-activated. Currently module statuses are stored on disk, this features adds the possibility of storing this status information in a database.
    
    **Use the command `php artisan module:v6:migrate` to have old modules active status migrated to the new system.**
    
### Changed

- Alternate way to define the namespace of modules in [PR #776](https://github.com/nWidart/laravel-modules/pull/776) by @daison12006013

    This allows to have the content of the module under an `src/` folder for example.
- **BREAKING** New way to handle active and inactive modules.

    Modules don't store their active status in their module.json file anymore, but in a file under the storage folder.
    Run `php artisan module:v6:migrate` to use the new system.
- **BREAKING** Renamed method `enabled` to `isEnabled` in `\Nwidart\Modules\Module`.
- **BREAKING** Renamed method `disabled` to `isDisabled` in `\Nwidart\Modules\Module`.
- **BREAKING** Renamed method `enabled` to `isEnabled` in `\Nwidart\Modules\FileRepository`.
- **BREAKING** Renamed method `disabled` to `isDisabled` in `\Nwidart\Modules\FileRepository`.
- **BREAKING** Removed the `__get` magic method on the `\Nwidart\Modules\Module` class. Use `get()` or `json()->get()` instead.
- The `module:make-listener` command now correctly uses the namespace configuration
- The generated Factories now has type hints for the `\Illuminate\Database\Eloquent\Factory` class
- Improved foreign key constraint generation
- Exception handling in the `SeedCommand` has been improved

## 5.0.1 - 2019-05-11

### Added

- `artisan module:route-provider` has a `--force` option to overwrite existing file

### Changed

- Fixing the `RouteServiceProvider` generation to properly use the `routes/web` and `routes/api` stubs
- Replacing `@stop` with `@endsection` in the view stub file
- `Module` class does not extend Laravel's Service Provider class anymore

## 5.0.0 - 2019-03-18

### Added

- Laravel 5.8 support

### Changed

- Deprecated string and array methods have been replaced
- Fixed caching not being cleared after disabling and enabling modules
- Update Route Provider stub to not affect the root namespace of the URL generator (#727)

### Removed

- **PHP 7.1 support** 

## 4.1.0 - 2019-03-04

### Changed

- Updated to laravel mix 4
- Add `--api` argument to `module:make-controller` command
- Seeding modules outside out `Modules` namespace

## 4.0.0 - 2018-09-30

### Added

- New way of handling routes by default using a RouteServiceProvider (instead of start.php)
- Laravel 5.7 support

### Changed

- Allow class resolution on short name and abstract
- `module:seed` accepts a `--class` option

## 3.3.1 - 2018-07-13

### Changed

- Added the ability to set a sub-namespace to controllers `module:make-controller Api\\TestController`

## 3.3.0 - 2018-06-21

### Changed

- `module:update` command has now the possibility to update all modules at once
- Fixing commented code for Laravel Mix

## 3.2.0 - 2018-04-16

### Added

- Added possibility to update all modules at once if any not specified (PR #523)

### Changed

- Mix: Fix css relative urls by changing the route folder (PR #521)
- Mix: Prevents every build from deleting previous Mix config file (PR #521)

## 3.1.0 - 2018-04-01

### Added

- Laravel mix configuration (https://nwidart.com/laravel-modules/v3/basic-usage/compiling-assets)

### Changed

- Allow symlinks in module path
- Returns the parameter `--class` to the `SeedCommand`.
- Generate folders recursively
- Removing link that has become a 404
- Fixed seed command exception typehint

### Removed

- Removed the optimize command on the `module:make-migration` command

## 3.0.1 - 2018-02-16

### Changed

- Update publish commands to use the new API to get all enabled modules (PR #483 )

## 3.0.0 - 2018-02-14

## Added

- Added support for laravel 5.6
- Using phpunit 7

## Changed

- **BC:** `Repository` class: renamed `enabled` to `allEnabled`
- **BC:** `Repository` class: renamed `disabled` to `allDisabled`
- **BC:** `Repository` class: renamed `active` to `enabled`
- **BC:** `Repository` class: renamed `notActive` to `disabled`

## Removed

- Dropped php 7.0 support
- **BC:** `Module` class: Deprecated `active()` method, use `enabled()`
- **BC:** `Module` class: Deprecated `notActive()` method, use `disabled()`
- **BC:** `Repository` class: Deprecated `addPath()` method, use `addLocation()`
- **BC:** `Repository` class: Deprecated `get()` method, use `find()`
- **BC:** `Repository` class: Deprecated `getUsed()` method, use `getUsedNow()`


## 2.7.0 - 2018-01-13

## Changed

- Rename the `before` method to `boot` in the `RouterServiceProvider` stub file
- Fixing caching issue if modules were loaded from a different directory
- Fixing how modules are loaded from vendor directory (#423 #417)
- Update to Mockery 1.0
- use default file stubs only if override does not exists
- Fix non well formed numeric value in seed command

## 2.6.0 - 2017-11-07

## Added

- Ability to customise the destination folder & namespace of a generated class
- Added `php artisan module:migrate-status` command
- `config_path()` helper for Lumen
- Added views tag to view config in ServiceProvider
- added package auto discovery for laravel 5.5 in generated module `composer.json`

## Changed

- Adding the ability to correctly load modules from multiple locations, together
- Custom seeder path now also used in the `module:seed` command

## 2.5.1 - 2017-10-13

## Changed

- added config_path helper to helpers for Lumen support
- updated readme on how to install laravel-modules in Lumen

## 2.5.0 - 2017-10-03

## Changed

- Making the path to migrations for `loadMigrationsFrom()` call dynamic based of configuration
- Making the factory path dynamic for master service provider & make-factory command
- Make the route file location dynamic in start.php based of `stubs.files.routes`
- Making the route path dynamic on the route service provider based of `stubs.files.routes`
- New structure in configuration to set which folders will be generated on `module:make` (old format still supported)
- Setting new sensible defaults to what folders to generate by default.
- Change the assets directory default location `resources/assets`

## 2.4.1 - 2017-09-27

## Changed

- Setting a default value for `modules.paths.modules` configuration key


## 2.4.0 - 2017-09-27

## Added

- New `module:make-resource` command to generate resource classes
- New `module:make-test` command to generate test classes

## Changed

- Improved error output of the `module:seed` command
- Marking methods that served as proxies in `Module` and `Repository` classes as deprecated for next major
- Fixed `module:make` and `module:make-provider` to generate the correct master service provider
- Tests: tests are now using `spatie/phpunit-snapshot-assertions` to make sure the generated files have the correct content
- Adding a sync option to the `module:make-job` command to make a synchronous job class
- Changed `module:make-event` command to allow duck typed events (not type hinted event class)
- Changed `module:make-listener` to allow a `--queued` option to make the event queueable
- Changed `module:make-listener` command to not use the full class typehint when class was previous imported

## 2.3.0 - 2017-09-26

## Added

- Ability to ignore some folders to generate
- Creating an module:unuse command to forget the previously saved module
- New command to generate Policy classes
- New command for creating factories
- New command for creating rules
- new `public_path` helper for Lumen

## Changed

- Refactored class names that generate something to be fully consistent

## 2.2.1 - 2017-09-14

## Changed

- Fixed class namespace to `Repository` in `ContractsServiceProvider`

## 2.2.0 - 2017-09-14

### Added

- Lumen compatibility with new way to register providers


## 2.1.0 - 2017-09-10

### Changed

- Register module migrations
- Fixed issue with `module:migrate-refresh` command
- Improved module loading of their service providers. Using laravel caching system. Allowing usage of deferred providers.
- Fixed path to module factories

## 2.0.0 - 2017-08-31

### Added

- Support Laravel 5.5


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
