---
name: laravel-modules-development
description: "Use for any task or question involving nWidart/laravel-modules. Activate if the user mentions modules, module:make, module commands, or a Modules/ directory structure."
license: MIT
metadata:
    author: nWidart
    ---
    @php
    /** @var \Laravel\Boost\Install\GuidelineAssist $assist */
    @endphp
    # Laravel Modules Development

    ## Documentation

    Use `search-docs` for detailed laravel-modules patterns and documentation.

    ## Basic Usage

    ### Creating a Module

    ```bash
    {{ $assist->artisanCommand('module:make Blog') }}
    ```

    ### Enabling / Disabling a Module

    ```bash
    {{ $assist->artisanCommand('module:enable Blog') }}
    {{ $assist->artisanCommand('module:disable Blog') }}
    ```

    ### Listing Modules

    ```bash
    {{ $assist->artisanCommand('module:list') }}
    ```

    ## Module Structure

    A generated module lives under `Modules/Blog/` and contains:

    ```
    Modules/Blog/
      app/
          Http/Controllers/
              Models/
                  Providers/
                    config/
                      database/
                          migrations/
                              seeders/
                                resources/
                                    views/
                                      routes/
                                          web.php
                                              api.php
                                                tests/
                                                  module.json
                                                    composer.json
                                                    ```

                                                    ## Generating Module Components

                                                    ### Controller

                                                    ```bash
                                                    {{ $assist->artisanCommand('module:make-controller BlogController Blog') }}
                                                    {{ $assist->artisanCommand('module:make-controller BlogController Blog --api') }}
                                                    ```

                                                    ### Model

                                                    ```bash
                                                    {{ $assist->artisanCommand('module:make-model Post Blog') }}
                                                    {{ $assist->artisanCommand('module:make-model Post Blog --migration') }}
                                                    {{ $assist->artisanCommand('module:make-model Post Blog --attributes --fillable=title,slug') }}
                                                    ```

                                                    ### Migration

                                                    ```bash
                                                    {{ $assist->artisanCommand('module:make-migration create_posts_table Blog') }}
                                                    ```

                                                    ### Seeder

                                                    ```bash
                                                    {{ $assist->artisanCommand('module:make-seed PostDatabaseSeeder Blog') }}
                                                    ```

                                                    ### Request

                                                    ```bash
                                                    {{ $assist->artisanCommand('module:make-request StorePostRequest Blog') }}
                                                    ```

                                                    ### Provider

                                                    ```bash
                                                    {{ $assist->artisanCommand('module:make-provider BlogServiceProvider Blog') }}
                                                    ```

                                                    ### Event & Listener

                                                    ```bash
                                                    {{ $assist->artisanCommand('module:make-event PostCreated Blog') }}
                                                    {{ $assist->artisanCommand('module:make-listener SendPostNotification Blog') }}
                                                    ```

                                                    #### Event discovery

                                                    Laravel's event discovery finds listeners inside modules, but the package adds no discovery paths. Add the module listener directories in `bootstrap/app.php`:

                                                    ```php
                                                    ->withEvents(discover: [
                                                        __DIR__.'/../app/Listeners',
                                                        __DIR__.'/../Modules/*/app/Listeners',
                                                    ])
                                                    ```

                                                    - Check the result with `{{ $assist->artisanCommand('event:list') }}`.
                                                    - In production, `{{ $assist->artisanCommand('event:cache') }}` (or `optimize`) caches discovery, so nothing is scanned per request.
                                                    - The glob also matches disabled modules. Their listeners are still discovered and run if the class autoloads.
                                                    - `$shouldDiscoverEvents` in a module `EventServiceProvider` does not discover anything. Laravel discovers only from its base `EventServiceProvider`, so use `withEvents` paths.
                                                    - The package maps files under `modules.paths.modules` to module classes (`Modules/Blog/app/Listeners/X.php` becomes `Modules\Blog\Listeners\X`). A `DiscoverEvents::guessClassNamesUsing()` call you make later, for example in `AppServiceProvider`, replaces this mapping.

                                                    ### Job

                                                    ```bash
                                                    {{ $assist->artisanCommand('module:make-job ProcessPost Blog') }}
                                                    {{ $assist->artisanCommand('module:make-job-middleware RateLimited Blog') }}
                                                    ```

                                                    Job middleware lands in `Modules/Blog/app/Jobs/Middleware` (namespace `Modules\Blog\Jobs\Middleware`).

                                                    ### Middleware

                                                    ```bash
                                                    {{ $assist->artisanCommand('module:make-middleware EnsureUserIsAdmin Blog') }}
                                                    ```

                                                    ### Command

                                                    ```bash
                                                    {{ $assist->artisanCommand('module:make-command SyncPosts Blog') }}
                                                    ```

                                                    ### Policy

                                                    ```bash
                                                    {{ $assist->artisanCommand('module:make-policy PostPolicy Blog') }}
                                                    ```

                                                    ### Resource

                                                    ```bash
                                                    # Generate a resource
                                                    {{ $assist->artisanCommand('module:make-resource PostResource Blog') }}

                                                    # Generate a resource collection (--collection, or a name ending in "Collection")
                                                    {{ $assist->artisanCommand('module:make-resource PostCollection Blog') }}
                                                    {{ $assist->artisanCommand('module:make-resource Posts Blog --collection') }}

                                                    # Generate a JSON:API resource (Laravel 13+; --collection wins over --json-api)
                                                    {{ $assist->artisanCommand('module:make-resource PostResource Blog --json-api') }}
                                                    ```

                                                    ### Config

                                                    ```bash
                                                    {{ $assist->artisanCommand('module:make-config stripe Blog') }}
                                                    ```

                                                    Creates `Modules/Blog/config/stripe.php`, read as `config('blog.stripe.key')`. Nested names work: `services/stripe` becomes `config('blog.services.stripe')`.

                                                    ## Running Module Migrations

                                                    ```bash
                                                    {{ $assist->artisanCommand('module:migrate Blog') }}
                                                    {{ $assist->artisanCommand('module:migrate-rollback Blog') }}
                                                    {{ $assist->artisanCommand('module:migrate-refresh Blog') }}
                                                    {{ $assist->artisanCommand('module:migrate-reset Blog') }}
                                                    ```

                                                    ## Inertia Support

                                                    When using Inertia.js inside a module:

                                                    ```bash
                                                    # Generate an Inertia-powered module (--inertia flag)
                                                    {{ $assist->artisanCommand('module:make Blog --inertia') }}

                                                    # Generate an Inertia page inside a module
                                                    {{ $assist->artisanCommand('module:make-inertia-page Index Blog') }}
                                                    {{ $assist->artisanCommand('module:make-inertia-page Index Blog --react') }}
                                                    {{ $assist->artisanCommand('module:make-inertia-page Index Blog --vue') }}
                                                    {{ $assist->artisanCommand('module:make-inertia-page Index Blog --svelte') }}

                                                    # Generate an Inertia component inside a module
                                                    {{ $assist->artisanCommand('module:make-inertia-component Button Blog') }}

                                                    # Publish the Inertia app.js entry point
                                                    {{ $assist->artisanCommand('module:publish-inertia') }}
                                                    ```

                                                    ## Key Conventions

                                                    - Module classes are namespaced as `Modules\{ModuleName}` (e.g. `Modules\Blog\Http\Controllers\PostController`).
                                                    - Module routes are loaded from `Modules/{Name}/routes/web.php` and `api.php`.
                                                    - Module views are referenced as `{lowercase-name}::{view}` (e.g. `blog::index`).
                                                    - Module configs live in `Modules/{Name}/config/{name}.php` and are merged at `{name}`.
                                                    - Module service providers are auto-discovered from `module.json` `providers` key.
                                                    - Keep business logic inside the module; avoid coupling modules together — use events/listeners instead.
                                                    - Use `module:publish-config`, `module:publish-migration`, or `module:publish-translation` to publish module assets to the host application.
