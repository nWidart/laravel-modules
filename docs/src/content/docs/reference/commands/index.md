---
title: Artisan commands
description: Every Artisan command the package registers, grouped by page, with its purpose and a link to its entry.
---

The package registers 72 Artisan commands. Each group has one page with the arguments, options and notes of every command. Open the group page for the shared behaviour of a group.

In a synopsis, `<name>` is a required argument, `[<name>]` an optional one, and `...` marks an argument that takes several values. Options follow the arguments.

`module:route-provider` is a generator even though its name has no `make-` prefix. It is listed with the generators.

## Module management

Commands that list, enable, disable, install, update and delete existing modules. Details: [Module management commands](/laravel-modules/reference/commands/module-management/).

| Command | Purpose |
| --- | --- |
| [`module:list`](/laravel-modules/reference/commands/module-management/#modulelist) | Lists the modules with their status, priority and path. |
| [`module:enable`](/laravel-modules/reference/commands/module-management/#moduleenable) | Marks the given modules as enabled. |
| [`module:disable`](/laravel-modules/reference/commands/module-management/#moduledisable) | Marks the given modules as disabled. |
| [`module:delete`](/laravel-modules/reference/commands/module-management/#moduledelete) | Deletes the module directory and removes the module from the activator. |
| [`module:use`](/laravel-modules/reference/commands/module-management/#moduleuse) | Stores a module name as the module to use when a generator command gets no `module` argument. |
| [`module:unuse`](/laravel-modules/reference/commands/module-management/#moduleunuse) | Forgets the module stored by `module:use`. |
| [`module:install`](/laravel-modules/reference/commands/module-management/#moduleinstall) | Installs a module from Composer or from a Git repository, then runs `module:update` for it. |
| [`module:update`](/laravel-modules/reference/commands/module-management/#moduleupdate) | Installs the Composer dependencies a module declares. |
| [`module:dump`](/laravel-modules/reference/commands/module-management/#moduledump) | Runs `composer dump -o -n -q` inside each module directory. |
| [`module:list-commands`](/laravel-modules/reference/commands/module-management/#modulelist-commands) | Lists the Artisan command classes found in each module. |
| [`module:lang`](/laravel-modules/reference/commands/module-management/#modulelang) | Compares the language directories of a module and reports missing files and keys. |
| [`module:prune`](/laravel-modules/reference/commands/module-management/#moduleprune) | Prunes the prunable Eloquent models of the given modules. It wraps Laravel `model:prune`. |
| [`module:model-show`](/laravel-modules/reference/commands/module-management/#modulemodel-show) | Shows information about an Eloquent model that lives in a module. It wraps Laravel `model:show`. |

## Generators

Commands that create a module or a file inside a module. Details: [Generator commands](/laravel-modules/reference/commands/generators/).

| Command | Purpose |
| --- | --- |
| [`module:make`](/laravel-modules/reference/commands/generators/#modulemake) | Creates one or more modules: the folder structure, `module.json`, `composer.json` and the starting classes. |
| [`module:make-action`](/laravel-modules/reference/commands/generators/#modulemake-action) | Creates an action class in a module. |
| [`module:make-cast`](/laravel-modules/reference/commands/generators/#modulemake-cast) | Creates an Eloquent cast class in a module. |
| [`module:make-channel`](/laravel-modules/reference/commands/generators/#modulemake-channel) | Creates a broadcast channel class in a module. |
| [`module:make-class`](/laravel-modules/reference/commands/generators/#modulemake-class) | Creates a class of any type in a module. The type decides the folder. |
| [`module:make-command`](/laravel-modules/reference/commands/generators/#modulemake-command) | Creates an Artisan command class in a module. |
| [`module:make-component`](/laravel-modules/reference/commands/generators/#modulemake-component) | Creates a Blade component class and its view in a module. |
| [`module:make-component-view`](/laravel-modules/reference/commands/generators/#modulemake-component-view) | Creates a Blade component view in a module. |
| [`module:make-config`](/laravel-modules/reference/commands/generators/#modulemake-config) | Creates a configuration file in a module. |
| [`module:make-controller`](/laravel-modules/reference/commands/generators/#modulemake-controller) | Creates a controller in a module. |
| [`module:make-enum`](/laravel-modules/reference/commands/generators/#modulemake-enum) | Creates an enum in a module. |
| [`module:make-event`](/laravel-modules/reference/commands/generators/#modulemake-event) | Creates an event class in a module. |
| [`module:make-event-provider`](/laravel-modules/reference/commands/generators/#modulemake-event-provider) | Creates the `EventServiceProvider` of a module. |
| [`module:make-exception`](/laravel-modules/reference/commands/generators/#modulemake-exception) | Creates an exception class in a module. |
| [`module:make-factory`](/laravel-modules/reference/commands/generators/#modulemake-factory) | Creates a model factory in a module. |
| [`module:make-helper`](/laravel-modules/reference/commands/generators/#modulemake-helper) | Creates a helper class in a module. |
| [`module:make-health-indicator`](/laravel-modules/reference/commands/generators/#modulemake-health-indicator) | Creates a health indicator class for the actuator in a module. |
| [`module:make-inertia-component`](/laravel-modules/reference/commands/generators/#modulemake-inertia-component) | Creates an Inertia component in a module. |
| [`module:make-inertia-page`](/laravel-modules/reference/commands/generators/#modulemake-inertia-page) | Creates an Inertia page in a module. |
| [`module:make-interface`](/laravel-modules/reference/commands/generators/#modulemake-interface) | Creates an interface in a module. |
| [`module:make-job`](/laravel-modules/reference/commands/generators/#modulemake-job) | Creates a queued job class in a module. |
| [`module:make-job-middleware`](/laravel-modules/reference/commands/generators/#modulemake-job-middleware) | Creates a job middleware class in a module. |
| [`module:make-listener`](/laravel-modules/reference/commands/generators/#modulemake-listener) | Creates an event listener in a module. |
| [`module:make-mail`](/laravel-modules/reference/commands/generators/#modulemake-mail) | Creates a mailable class in a module. |
| [`module:make-middleware`](/laravel-modules/reference/commands/generators/#modulemake-middleware) | Creates a middleware class in a module. |
| [`module:make-migration`](/laravel-modules/reference/commands/generators/#modulemake-migration) | Creates a migration in a module. |
| [`module:make-model`](/laravel-modules/reference/commands/generators/#modulemake-model) | Creates an Eloquent model in a module, and optionally the files that go with it. |
| [`module:make-notification`](/laravel-modules/reference/commands/generators/#modulemake-notification) | Creates a notification class in a module. |
| [`module:make-observer`](/laravel-modules/reference/commands/generators/#modulemake-observer) | Creates a model observer in a module. |
| [`module:make-policy`](/laravel-modules/reference/commands/generators/#modulemake-policy) | Creates a policy class in a module. |
| [`module:make-provider`](/laravel-modules/reference/commands/generators/#modulemake-provider) | Creates a service provider in a module. |
| [`module:make-replacement`](/laravel-modules/reference/commands/generators/#modulemake-replacement) | Creates a replacement key class for stubs in a module. |
| [`module:make-repository`](/laravel-modules/reference/commands/generators/#modulemake-repository) | Creates a repository class in a module. |
| [`module:make-request`](/laravel-modules/reference/commands/generators/#modulemake-request) | Creates a form request class in a module. |
| [`module:make-resource`](/laravel-modules/reference/commands/generators/#modulemake-resource) | Creates an API resource class in a module. |
| [`module:make-rule`](/laravel-modules/reference/commands/generators/#modulemake-rule) | Creates a validation rule in a module. |
| [`module:make-scope`](/laravel-modules/reference/commands/generators/#modulemake-scope) | Creates an Eloquent scope class in a module. |
| [`module:make-seed`](/laravel-modules/reference/commands/generators/#modulemake-seed) | Creates a database seeder in a module. |
| [`module:make-service`](/laravel-modules/reference/commands/generators/#modulemake-service) | Creates a service class in a module. |
| [`module:make-test`](/laravel-modules/reference/commands/generators/#modulemake-test) | Creates a test class in a module. |
| [`module:make-trait`](/laravel-modules/reference/commands/generators/#modulemake-trait) | Creates a trait in a module. |
| [`module:make-view`](/laravel-modules/reference/commands/generators/#modulemake-view) | Creates a Blade view in a module. |
| [`module:route-provider`](/laravel-modules/reference/commands/generators/#moduleroute-provider) | Creates the `RouteServiceProvider` of a module. |

## Database

Commands that run, roll back, reset, refresh and seed module migrations. Details: [Database commands](/laravel-modules/reference/commands/database/).

| Command | Purpose |
| --- | --- |
| [`module:migrate`](/laravel-modules/reference/commands/database/#modulemigrate) | Runs the migrations of the given modules through Laravel `migrate`. |
| [`module:migrate-fresh`](/laravel-modules/reference/commands/database/#modulemigrate-fresh) | Drops all tables of the database, then runs the application migrations and the migrations of the given modules. |
| [`module:migrate-refresh`](/laravel-modules/reference/commands/database/#modulemigrate-refresh) | Resets and runs again the migrations of the given modules. |
| [`module:migrate-reset`](/laravel-modules/reference/commands/database/#modulemigrate-reset) | Rolls back every migration of the given modules that ran. |
| [`module:migrate-rollback`](/laravel-modules/reference/commands/database/#modulemigrate-rollback) | Rolls back the last batch of migrations of the given modules. |
| [`module:migrate-status`](/laravel-modules/reference/commands/database/#modulemigrate-status) | Shows which migrations of the given modules ran. |
| [`module:seed`](/laravel-modules/reference/commands/database/#moduleseed) | Runs the seeders of the given modules through Laravel `db:seed`. |

## Publish

Commands that copy module files to the application. Details: [Publish commands](/laravel-modules/reference/commands/publish/).

| Command | Purpose |
| --- | --- |
| [`module:publish`](/laravel-modules/reference/commands/publish/#modulepublish) | Copies the assets of a module to the public assets path. |
| [`module:publish-config`](/laravel-modules/reference/commands/publish/#modulepublish-config) | Publishes the configuration files of a module to the application `config` directory. |
| [`module:publish-inertia`](/laravel-modules/reference/commands/publish/#modulepublish-inertia) | Publishes the Inertia `app.js` entry file to `resources/js/app.js`. |
| [`module:publish-migration`](/laravel-modules/reference/commands/publish/#modulepublish-migration) | Copies the migrations of a module to the application migration path. |
| [`module:publish-translation`](/laravel-modules/reference/commands/publish/#modulepublish-translation) | Copies the language files of a module to the application `lang` directory. |

## Utility

Commands for setup, autoload rewriting, version 5 status migration and PHPUnit coverage. Details: [Utility commands](/laravel-modules/reference/commands/utility/).

| Command | Purpose |
| --- | --- |
| [`module:setup`](/laravel-modules/reference/commands/utility/#modulesetup) | Creates the modules directory and the public assets directory when they do not exist. |
| [`module:composer-update`](/laravel-modules/reference/commands/utility/#modulecomposer-update) | Rewrites the legacy `autoload.psr-4` entry of a module `composer.json` so that the module namespace maps to the app folder. |
| [`module:v6:migrate`](/laravel-modules/reference/commands/utility/#modulev6migrate) | Copies the `active` field of each `module.json` (the version 5 way to store the status) into the activator. |
| [`module:update-phpunit-coverage`](/laravel-modules/reference/commands/utility/#moduleupdate-phpunit-coverage) | Adds the app folder of each enabled module to the `<source><include>` list of `phpunit.xml`, and removes the entries of modules that are not enabled. |
