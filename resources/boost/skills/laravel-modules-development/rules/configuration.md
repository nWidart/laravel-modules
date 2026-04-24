# Laravel Modules Configuration

Publish config: `php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"`

The config file lands at `config/modules.php`.

## Key Configuration Options

### Namespace

```php
'namespace' => 'Modules',
```

The root PHP namespace for all modules. Changing this requires updating all existing module namespaces.

### Paths

```php
'paths' => [
    'modules' => base_path('Modules'),       // Where modules live
    'assets'  => public_path('modules'),     // Published assets destination
    'migration' => base_path('database/migrations'),  // Publish target for migrations
    'app_folder' => 'app/',                  // Sub-directory inside each module for app code
],
```

### Generator Paths

Configure where each generated file lands inside a module. Set `generate` to `false` to skip creating that directory:

```php
'paths' => [
    'generator' => [
        'action'         => ['path' => 'app/Actions',          'generate' => false],
        'cast'           => ['path' => 'app/Casts',            'generate' => false],
        'channel'        => ['path' => 'app/Broadcasting',     'generate' => false],
        'command'        => ['path' => 'app/Console/Commands', 'generate' => false],
        'component-class'=> ['path' => 'app/View/Components',  'generate' => false],
        'controller'     => ['path' => 'app/Http/Controllers', 'generate' => true],
        'enum'           => ['path' => 'app/Enums',            'generate' => false],
        'event'          => ['path' => 'app/Events',           'generate' => false],
        'exception'      => ['path' => 'app/Exceptions',       'generate' => false],
        'factory'        => ['path' => 'database/factories',   'generate' => true],
        'helper'         => ['path' => 'app/Helpers',          'generate' => false],
        'interface'      => ['path' => 'app/Interfaces',       'generate' => false],
        'job'            => ['path' => 'app/Jobs',             'generate' => false],
        'listener'       => ['path' => 'app/Listeners',        'generate' => false],
        'mail'           => ['path' => 'app/Mail',             'generate' => false],
        'middleware'     => ['path' => 'app/Http/Middleware',  'generate' => false],
        'migration'      => ['path' => 'database/migrations',  'generate' => true],
        'model'          => ['path' => 'app/Models',           'generate' => true],
        'notification'   => ['path' => 'app/Notifications',    'generate' => false],
        'observer'       => ['path' => 'app/Observers',        'generate' => false],
        'policy'         => ['path' => 'app/Policies',         'generate' => false],
        'provider'       => ['path' => 'app/Providers',        'generate' => true],
        'repository'     => ['path' => 'app/Repositories',     'generate' => false],
        'request'        => ['path' => 'app/Http/Requests',    'generate' => false],
        'resource'       => ['path' => 'app/Http/Resources',   'generate' => false],
        'rule'           => ['path' => 'app/Rules',            'generate' => false],
        'scope'          => ['path' => 'app/Models/Scopes',    'generate' => false],
        'seeder'         => ['path' => 'database/seeders',     'generate' => true],
        'service'        => ['path' => 'app/Services',         'generate' => false],
        'test'           => ['path' => 'tests/Feature',        'generate' => true],
        'test-unit'      => ['path' => 'tests/Unit',           'generate' => true],
        'trait'          => ['path' => 'app/Traits',           'generate' => false],
        'view'           => ['path' => 'resources/views',      'generate' => true],
        'routes'         => ['path' => 'routes',               'generate' => true],
    ],
],
```

### Auto-Discovery

```php
'auto-discover' => [
    'migrations'   => true,   // Auto-register module migration paths on boot
    'translations' => false,  // Auto-register module lang namespaces on boot
],
```

When `migrations` is `true`, you don't need `loadMigrationsFrom()` in your service provider.
When `translations` is `false`, you must call `loadTranslationsFrom()` manually.

### Activators

Activators control where module enabled/disabled state is persisted:

```php
'activators' => [
    'file' => [
        'class'        => FileActivator::class,
        'statuses-file' => base_path('modules_statuses.json'),
        'cache-key'    => 'activator.installed',
        'cache-lifetime' => 604800,
    ],
],
'activator' => 'file',
```

The `modules_statuses.json` file stores enabled/disabled state:

```json
{
    "Blog": true,
    "Shop": false
}
```

### Stubs

Customise generated file templates:

```php
'stubs' => [
    'enabled'  => false,
    'path'     => base_path('stubs/nwidart-stubs'),
    'gitkeep'  => true,
],
```

Set `enabled` to `true` and publish stubs with:
```bash
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider" --tag="stubs"
```

### Inertia

```php
'inertia' => [
    'frontend' => 'vue',  // 'vue', 'react', or 'svelte'
],
```

## module.json Schema

Each module has a `module.json` at its root:

```json
{
    "name": "Blog",
    "alias": "blog",
    "description": "Blog feature module",
    "keywords": ["blog", "posts"],
    "active": 1,
    "order": 1,
    "providers": [
        "Modules\\Blog\\Providers\\BlogServiceProvider",
        "Modules\\Blog\\Providers\\RouteServiceProvider"
    ],
    "aliases": {},
    "files": []
}
```

| Field | Purpose |
|---|---|
| `name` | StudlyCase module name — matches directory name |
| `alias` | lowercase identifier used in view/config/lang namespaces |
| `active` | `1` enabled, `0` disabled |
| `order` | Integer load priority; lower = loaded first |
| `providers` | Fully-qualified class names of service providers to register |
| `aliases` | Facade aliases to register |
| `files` | PHP files to autoload on every request |

**Critical**: Every provider that registers routes, bindings, or config must be listed under `providers`. Missing entries cause silent failures.
