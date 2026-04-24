# Module Architecture Patterns

## Module Directory Structure

```
Modules/
└── Blog/
    ├── app/
    │   ├── Actions/
    │   ├── Console/Commands/
    │   ├── Events/
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   ├── Middleware/
    │   │   └── Requests/
    │   ├── Jobs/
    │   ├── Listeners/
    │   ├── Mail/
    │   ├── Models/
    │   ├── Notifications/
    │   ├── Observers/
    │   ├── Policies/
    │   ├── Providers/
    │   │   ├── BlogServiceProvider.php
    │   │   └── RouteServiceProvider.php
    │   ├── Repositories/
    │   ├── Services/
    │   └── View/Components/
    ├── config/
    │   └── config.php
    ├── database/
    │   ├── factories/
    │   ├── migrations/
    │   └── seeders/
    ├── resources/
    │   ├── assets/
    │   ├── lang/
    │   │   └── en/
    │   │       └── messages.php
    │   └── views/
    ├── routes/
    │   ├── api.php
    │   └── web.php
    ├── tests/
    │   ├── Feature/
    │   └── Unit/
    ├── composer.json
    └── module.json
```

## Service Provider Architecture

Each module registers two service providers in `module.json`.

### ModuleServiceProvider

Handles boot-time registration of resources:

```php
namespace Modules\Blog\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;

class BlogServiceProvider extends ServiceProvider
{
    use PathNamespace;

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Bind interfaces to implementations
        $this->app->bind(
            \Modules\Blog\Contracts\PostRepositoryInterface::class,
            \Modules\Blog\Repositories\PostRepository::class,
        );
    }

    public function boot(): void
    {
        // Only needed when auto-discover.migrations = false
        $this->loadMigrationsFrom(module_path('Blog', 'database/migrations'));

        $this->loadViewsFrom(module_path('Blog', 'resources/views'), 'blog');

        // Only needed when auto-discover.translations = false
        $this->loadTranslationsFrom(module_path('Blog', 'resources/lang'), 'blog');

        $this->mergeConfigFrom(module_path('Blog', 'config/config.php'), 'blog');
    }
}
```

### RouteServiceProvider

Loads the module's web and API routes:

```php
namespace Modules\Blog\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\Blog\Http\Controllers';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Blog', 'routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Blog', 'routes/api.php'));
    }
}
```

## Naming Conventions

| Resource | Pattern | Example |
|---|---|---|
| PHP Namespace | `Modules\{Studly}` | `Modules\Blog\Http\Controllers\PostController` |
| View reference | `{lower}::{path}` | `view('blog::posts.index')` |
| Config key | `{lower}.{key}` | `config('blog.posts_per_page')` |
| Translation key | `{lower}::{file}.{key}` | `__('blog::messages.post_created')` |
| Route name | `{lower}.{name}` (convention) | `route('blog.posts.index')` |
| Asset URL | `modules/{lower}/` | `/modules/blog/css/app.css` |

## Module Facade & Helpers

```php
use Nwidart\Modules\Facades\Module;

// Collection operations
Module::all();                         // All modules (enabled + disabled)
Module::allEnabled();                  // Only enabled modules
Module::allDisabled();                 // Only disabled modules
Module::count();                       // Total module count
Module::getOrdered('asc');             // Sorted by 'order' field

// Single module
$module = Module::find('Blog');        // Module instance or null
$module = Module::findOrFail('Blog');  // Throws if not found
Module::isEnabled('Blog');             // bool
Module::isDisabled('Blog');            // bool

// Paths and config
Module::getModulePath('Blog');         // Full path to Modules/Blog/
Module::config('modules.namespace');  // Read package config

// Lifecycle
Module::delete($module);              // Delete a module
```

```php
// Global helpers
module_path('Blog', 'app/Http/Controllers');   // Modules/Blog/app/Http/Controllers
module_vite('Blog', 'resources/js/app.js');    // Vite asset URL for module asset
config_path('blog');                            // config/blog.php path
```

## Blade Directives

```blade
@module('Blog')
    {{-- Only rendered when Blog module is enabled --}}
    <a href="{{ route('blog.posts.index') }}">Blog</a>
@endmodule
```

Equivalent to: `@if(module('Blog'))...@endif`

## Inter-Module Communication

### Correct: Events and Listeners

Never import classes from another module directly. Use events/listeners so that disabling one module doesn't crash another.

```php
// Blog module fires an event
namespace Modules\Blog\Events;

class PostPublished
{
    public function __construct(public readonly Post $post) {}
}

// In a controller or service
event(new PostPublished($post));
```

```php
// Notifications module registers a listener in its EventServiceProvider
namespace Modules\Notifications\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Modules\Blog\Events\PostPublished::class => [
            \Modules\Notifications\Listeners\NotifySubscribers::class,
        ],
    ];
}
```

### Correct: Shared Module for Cross-Cutting Concerns

When multiple modules need the same interface or utility, create a `Core` or `Shared` module:

```
Modules/
├── Core/          # Shared contracts, base classes, helpers
├── Blog/
└── Shop/
```

Both `Blog` and `Shop` can safely depend on `Modules\Core\...` since Core is always enabled.

### Incorrect: Direct Cross-Module Class Imports

```php
// WRONG — if Shop is disabled, Blog will throw a fatal error
use Modules\Shop\Services\PricingService;
```

## Auto-Discovery

When `auto-discover.migrations` is `true` (the default), all enabled modules have their migrations auto-registered at boot. You don't need `loadMigrationsFrom()` in your service provider.

When `auto-discover.translations` is `true`, module lang namespaces are also auto-registered.

Both can be configured per-environment in `config/modules.php`.

## Module Load Order

The `order` field in `module.json` controls load order. Lower numbers load first. Use this when:
- Module B depends on Module A's service provider being registered first
- A `Core` module must boot before feature modules

```json
{ "name": "Core", "order": 1 }
{ "name": "Blog", "order": 5 }
{ "name": "Shop", "order": 5 }
```

## Inertia.js Support

```bash
# Scaffold a module with Inertia pages
php artisan module:make Blog --inertia

# Generate pages
php artisan module:make-inertia-page Index Blog --vue
php artisan module:make-inertia-page Show Blog --react
php artisan module:make-inertia-page Create Blog --svelte

# Reusable Inertia components
php artisan module:make-inertia-component PostCard Blog

# Publish the app.js entry point
php artisan module:publish-inertia
```

Default frontend is configured in `config/modules.php` under `inertia.frontend` ('vue', 'react', or 'svelte').

## Module Activation and Deactivation

```bash
php artisan module:enable Blog    # Registers providers on next boot
php artisan module:disable Blog   # Skips providers on next boot; 404 on all module routes
```

Activation state is stored in `modules_statuses.json` at the project root. Commit this file to source control to ensure consistent module state across environments.

After enabling/disabling modules, clear the application cache:
```bash
php artisan optimize:clear
```
