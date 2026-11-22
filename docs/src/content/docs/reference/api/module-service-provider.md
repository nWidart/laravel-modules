---
title: Module service provider
description: The Support\ModuleServiceProvider base class of a module provider, its properties and methods, and what it registers for a module.
---

`Nwidart\Modules\Support\ModuleServiceProvider` extends `Illuminate\Support\ServiceProvider` and uses [`PathNamespace`](/laravel-modules/reference/api/contracts/#pathnamespace). `module:make` generates the main provider of a module from `scaffold/provider`, extending this class.

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
| `boot(): void` | Public. Runs `registerCommands()`, `registerCommandSchedules()`, `registerTranslations()`, `registerViews()`, and `registerHealthIndicators()`, then calls `loadMigrationsFrom()` with the module migration folder (default `database/migrations`). Also calls `registerConfig()` when a subclass skipped `parent::register()`. See [When the config is available](#when-the-config-is-available). |
| `register(): void` | Public. Calls `registerConfig()`, then registers each class in `$providers` via `$this->app->register()`. |
| `registerCommands(): void` | Protected. Registers the classes in `$commands`. |
| `registerCommandSchedules(): void` | Protected. Skips registration unless `configureSchedules()` is overridden. When overridden, waits for `booted()` and calls `configureSchedules()` whenever `Schedule` resolves. |
| `configureSchedules(Schedule $schedule): void` | Protected. Empty hook called by `registerCommandSchedules()` with the resolved `Schedule`. |
| `registerTranslations(): void` | Protected. See [Translations](#translations). |
| `registerConfig(): void` | Protected. See [Config files](#config-files). |
| `registerViews(): void` | Protected. See [Views and Blade components](#views-and-blade-components). |
| `registerHealthIndicators(): void` | Protected. Registers `$healthIndicators` with the actuator as `module:<nameLower>:<key>`. See [Health indicators](#health-indicators). |

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
| other files (e.g. `settings.php`, `nested/options.php`) | `<nameLower>.<dot path>` without `.php`. Adjacent repeated segments collapse: `blog.php` becomes `blog`, `blog/settings.php` becomes `blog.settings`. |

The provider merges config in two steps:

1. Module files merge via `array_replace_recursive()`, starting with `config.php` followed by remaining files sorted by path. Dedicated files override matching keys in `config.php`. Lists in dedicated files replace matching lists position by position.
2. Application values under the module key (such as a published `config/<nameLower>.php`) merge over the module config and take precedence:
   - Associative arrays merge recursively, key by key.
   - Lists, empty arrays `[]`, and scalars replace the module value as a whole.
   - Keys omitted by the application keep the module value. Keys set only by the application stay.

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

It is not available in `register()` of a provider that registers earlier, such as a package discovered before this one. Read the module config in `boot()` instead.

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

`registerViews()` registers the view namespace `<nameLower>` via `loadViewsFrom()`. It searches existing `<view path>/modules/<nameLower>` directories from `view.paths` first, then the module views folder.

It registers the Blade component namespace `<modules.namespace>\<Name>\View\Components` with prefix `<nameLower>` via `callAfterResolving('blade.compiler')`. Booting therefore does not resolve the Blade compiler eagerly. The `View\Components` segment is fixed and ignores `paths.generator.component-class`.

### Translations

`registerTranslations()` loads namespaced (`<nameLower>`) and JSON translations. When `lang_path('modules/<nameLower>')` exists (from `module:publish-translation`), it loads from there; otherwise it loads from `modules.paths.generator.lang.path`.

`auto-discover.translations` (default `false`) adds a second registration in `LaravelModulesServiceProvider`. For each enabled module, it registers the module `lang` folder under `<nameLower>` and as a JSON path. It never reads `lang_path('modules/<nameLower>')`.

- With `false`, translations load only when the module provider extends `ModuleServiceProvider`.
- With `true`, modules without `ModuleServiceProvider` still get translations. When both run, `ModuleServiceProvider` runs later, so published translations in `lang/modules/<nameLower>` win.

### Migrations

`boot()` calls `loadMigrationsFrom()` with the module migration folder so `php artisan migrate` includes it. `auto-discover.migrations` (default `true`) registers the same folder in `LaravelModulesServiceProvider`. See [Module migrations and `php artisan migrate`](/laravel-modules/reference/commands/database/#module-migrations-and-php-artisan-migrate).

### Health indicators

`registerHealthIndicators()` registers each entry of `$healthIndicators` in `actuator.health.indicators` as `module:<nameLower>:<key>`. Nothing is registered when `nwidart/laravel-actuator` is not installed or `modules.actuator.enabled` is `false`. An indicator already defined by the application under the same name is kept. See [Actuator integration](/laravel-modules/reference/api/actuator/) and [Report module health with the actuator](/laravel-modules/guides/production/health-checks/).
