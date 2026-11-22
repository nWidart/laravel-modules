---
title: Module service provider
description: The Support\ModuleServiceProvider base class of a module provider, its properties and methods, and what it registers for a module.
---

`Nwidart\Modules\Support\ModuleServiceProvider` extends `Illuminate\Support\ServiceProvider` and uses [`PathNamespace`](/laravel-modules/reference/api/contracts/#traits). `module:make` generates the main provider of a module from the stub `scaffold/provider`, and that provider extends this class.

## Properties

| Property | Type | Description |
| --- | --- | --- |
| `$name` | `string` | The module name. Required. |
| `$nameLower` | `string` | The lower-case module name. Required. It is the view namespace, the translation namespace and the root config key. |
| `$commands` | `string[]` | Artisan command classes to register. Default `[]`. |
| `$providers` | `string[]` | Provider classes to register. Default `[]`. |
| `$healthIndicators` | `array<string, class-string\|array{0: class-string, 1?: array}>` | Health indicators for `nwidart/laravel-actuator`, keyed by indicator name. A value is a class name or `[class, [constructor arguments]]`. Default `[]`. |

The constructor throws `LogicException` with the message `Module service provider must define both $name and $nameLower properties.` when either property is not set.

## Methods

| Signature | Description |
| --- | --- |
| `boot(): void` | Public. Calls, in order: `registerCommands()`, `registerCommandSchedules()`, `registerTranslations()`, `registerViews()`, `registerHealthIndicators()`, then `loadMigrationsFrom()` with the module migration folder (`modules.paths.generator.migration.path`, `database/migrations` when unset). It also calls `registerConfig()` when `register()` did not run through this class, see [When the config is available](#when-the-config-is-available). |
| `register(): void` | Public. Calls `registerConfig()`, then registers each class of `$providers` with `$this->app->register()`. |
| `registerCommands(): void` | Protected. Registers the classes of `$commands`. |
| `registerCommandSchedules(): void` | Protected. Does nothing unless the class overrides `configureSchedules()`. Otherwise it waits until the application has booted and calls `configureSchedules()` each time a `Schedule` instance is resolved. |
| `configureSchedules(Schedule $schedule): void` | Protected. Empty. `registerCommandSchedules()` calls it with the `Schedule`, so a provider that overrides it defines the schedules of the module. |
| `registerTranslations(): void` | Protected. See [Translations](#translations). |
| `registerConfig(): void` | Protected. See [Config files](#config-files). |
| `registerViews(): void` | Protected. See [Views and Blade components](#views-and-blade-components). |
| `registerHealthIndicators(): void` | Protected. Registers each entry of `$healthIndicators` with the actuator as `module:<nameLower>:<key>`. See [Report module health with the actuator](/laravel-modules/guides/production/health-checks/). |

```php
class BlogServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Blog';

    protected string $nameLower = 'blog';

    protected array $commands = [\Modules\Blog\Console\PruneCommand::class];

    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command('blog:prune')->daily();
    }
}
```

## What the provider registers

| Part of the module | Registered by | Name |
| --- | --- | --- |
| Config | `registerConfig()`, called by `register()` | Config key `<nameLower>`, or `<nameLower>.<file>`. See [Config files](#config-files). |
| Views | `registerViews()` | View namespace `<nameLower>`. |
| Blade components | `registerViews()` | Component prefix `<nameLower>`. |
| Translations | `registerTranslations()` | Translation namespace `<nameLower>`, and JSON translations. |
| Migrations | `boot()` | The migration folder, through `loadMigrationsFrom()`. See [Migrations](#migrations). |
| Commands | `registerCommands()` | The classes of `$commands`. |
| Schedules | `registerCommandSchedules()` | The events that `configureSchedules()` defines. |
| Sub-providers | `register()`, after the config | The classes of `$providers`. |
| Health indicators | `registerHealthIndicators()` | The actuator components `module:<nameLower>:<key>`, when the actuator is installed and `modules.actuator.enabled` is `true`. |
| Publish tags | `registerConfig()`, `registerViews()` | `config`, `views` and `<nameLower>-module-views`. See [Publish tags](#publish-tags). |

### Config files

`registerConfig()` reads every `.php` file below the module config folder (`modules.paths.generator.config.path`). It does nothing when the folder does not exist, and outside the console when the configuration is cached.

| File | Config key |
| --- | --- |
| `config.php` | `<nameLower>` |
| any other file, for example `settings.php` or `nested/options.php` | `<nameLower>.<path with dots, without .php>`, with adjacent repeated segments removed: `blog.php` gives `blog`, `blog/settings.php` gives `blog.settings` |

The provider merges the config in two steps:

1. It merges the module files together with `array_replace_recursive()`. `config.php` is merged first, then the other files by path. A dedicated file therefore wins over a key of the same name in `config.php`, and a list in a dedicated file replaces the matching list in `config.php` position by position.
2. It merges the values the application already has under the module key (for example a copy in `config/<nameLower>.php`) over the result. The application values win.
   - Where the application value and the module value are both associative arrays, they merge key by key, recursively.
   - Otherwise the application value replaces the module value as a whole. This includes a list (even a shorter one), an empty array `[]`, and a scalar.
   - Keys the application does not set keep the module value. Keys only the application sets are kept.

The module `'tags' => ['a', 'b', 'c']` with the application `'tags' => ['x']` gives `['x']`. With the application `'tags' => []` it gives `[]`. An empty array in the application also clears an associative module value: `'cache' => []` drops the `cache` defaults of the module.

With a cached configuration the merge is skipped, because the cache already holds the merged values.

```php
// Modules/Blog/config/config.php
return ['posts_per_page' => 15];

// config/blog.php (application)
return ['posts_per_page' => 99];

config('blog.posts_per_page'); // 99
```

#### When the config is available

`register()` calls `registerConfig()` before it registers `$providers`. The module config is available:

- in the `register()` of the sub-providers of the module (the route provider, the event provider and the other `$providers`);
- in the `register()` of providers that register after the module provider;
- in the `boot()` of every provider, including providers that boot before the module provider.

It is not available in the `register()` of a provider that registers before the module provider, for example a package that Laravel discovers before this one. Read the module config in its `boot()`.

A provider that overrides `register()` without calling `parent::register()` keeps working: `boot()` then calls `registerConfig()`. To read the module config while the provider registers, call `parent::register()` before that code.

`registerConfig()` is safe to call a second time: it gives the same config and the same publish paths.

To keep a module from merging its config, override `registerConfig()` with an empty method. Leaving `registerConfig()` out of an overridden `boot()` does not skip the merge, because `register()` already ran it.

An override of `registerConfig()` runs during `register()`. Do the work that needs a service that exists only at boot time in `boot()` instead.

`registerConfig()` calls `module_path($name)`, which throws `Module [<Name>] does not exist!` when `$name` does not match an existing module. This runs in `register()`, so a provider that overrides `boot()` hits the exception too. The generated providers always match.

### Publish tags

In the console, the provider registers these publish groups. See [vendor:publish tags](/laravel-modules/reference/commands/publish/#vendorpublish-tags).

| Tag | Source | Destination |
| --- | --- | --- |
| `config` | each module config file | `config_path()`, at the path that maps back to the config key (`blog.settings` goes to `config/blog/settings.php`) |
| `views` and `<nameLower>-module-views` | the module views folder (`modules.paths.generator.views.path`) | `resources/views/modules/<nameLower>` |

### Views and Blade components

`registerViews()` registers the view namespace `<nameLower>` with `loadViewsFrom()`. The folders are searched in this order: the published folders `<view path>/modules/<nameLower>` of each entry of `view.paths` that exists, then the module views folder.

It also calls `Blade::componentNamespace()` with the namespace `<modules.namespace>\<Name>\View\Components` and the prefix `<nameLower>`. The folder part `View\Components` is fixed. It does not follow the `component-class` generator path.

### Translations

`registerTranslations()` loads the translations under the namespace `<nameLower>`, and the JSON translations, from `lang_path('modules/<nameLower>')` when that folder exists (the destination of `module:publish-translation`), otherwise from the module language folder (`modules.paths.generator.lang.path`).

The key `auto-discover.translations` (default `false`) is a second registration. `LaravelModulesServiceProvider` adds, for each enabled module, the module language folder under the namespace `<nameLower>` and as a JSON path. It never reads `lang_path('modules/<nameLower>')`.

- With `false`, a module has translations only when its provider extends `ModuleServiceProvider`.
- With `true`, a module gets them without such a provider. When both registrations apply, they register the same namespace. The provider registers after the package hook, so the published folder `lang/modules/<nameLower>` wins when it exists.

### Migrations

`boot()` calls `loadMigrationsFrom()` with the module migration folder. Laravel's migrator then includes it in `php artisan migrate`. The key `auto-discover.migrations` (default `true`) registers the same folder from `LaravelModulesServiceProvider`. See [Module migrations and `php artisan migrate`](/laravel-modules/reference/commands/database/#module-migrations-and-php-artisan-migrate) for what each registration and `module:migrate` cover.
