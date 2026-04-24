# Module Generator Commands

All generators follow the pattern: `php artisan module:make-{type} {Name} {ModuleName}`

## Controllers

```bash
php artisan module:make-controller PostController Blog
php artisan module:make-controller PostController Blog --api        # Resourceful API controller
php artisan module:make-controller PostController Blog --invokable  # Single-action controller
php artisan module:make-controller PostController Blog --plain      # Empty controller
```

## Models

```bash
php artisan module:make-model Post Blog
php artisan module:make-model Post Blog --migration  # Create model + migration together
php artisan module:make-model Post Blog --factory    # Create model + factory
php artisan module:make-model Post Blog --fillable=title,body  # Set $fillable
```

## Migrations & Database

```bash
php artisan module:make-migration create_posts_table Blog
php artisan module:make-migration add_slug_to_posts_table Blog
php artisan module:make-factory PostFactory Blog
php artisan module:make-seed PostDatabaseSeeder Blog
```

## Requests & Resources

```bash
php artisan module:make-request StorePostRequest Blog
php artisan module:make-request UpdatePostRequest Blog
php artisan module:make-resource PostResource Blog
php artisan module:make-resource PostCollection Blog --collection
```

## Policies & Rules

```bash
php artisan module:make-policy PostPolicy Blog
php artisan module:make-rule UniqueSlug Blog
```

## Events, Listeners & Observers

```bash
php artisan module:make-event PostCreated Blog
php artisan module:make-event PostPublished Blog
php artisan module:make-listener SendPostNotification Blog
php artisan module:make-listener SendPostNotification Blog --event=PostCreated
php artisan module:make-observer PostObserver Blog
```

## Jobs, Mail & Notifications

```bash
php artisan module:make-job ProcessPost Blog
php artisan module:make-job ProcessPost Blog --sync   # Synchronous job
php artisan module:make-mail WelcomeMail Blog
php artisan module:make-notification PostPublished Blog
```

## Commands, Providers & Middleware

```bash
php artisan module:make-command SyncPosts Blog
php artisan module:make-provider BlogAuthServiceProvider Blog
php artisan module:make-middleware EnsureUserIsAdmin Blog
```

## Service & Repository Classes

```bash
php artisan module:make-service PostService Blog
php artisan module:make-repository PostRepository Blog
php artisan module:make-action CreatePost Blog
php artisan module:make-class PostFormatter Blog
php artisan module:make-interface PostRepositoryInterface Blog
php artisan module:make-trait HasSlug Blog
```

## Enums & Casts

```bash
php artisan module:make-enum PostStatus Blog
php artisan module:make-cast MoneyValue Blog
```

## Tests

```bash
php artisan module:make-test PostFeatureTest Blog         # Feature test
php artisan module:make-test PostUnitTest Blog --unit     # Unit test
```

## Inertia Pages & Components

```bash
php artisan module:make Blog --inertia                         # Full Inertia module scaffold
php artisan module:make-inertia-page Index Blog                # Page (uses default frontend)
php artisan module:make-inertia-page Index Blog --vue
php artisan module:make-inertia-page Index Blog --react
php artisan module:make-inertia-page Index Blog --svelte
php artisan module:make-inertia-component PostCard Blog        # Reusable component
```

## Generated File Locations

| Generator | Output Path |
|---|---|
| Controller | `Modules/Blog/app/Http/Controllers/` |
| Model | `Modules/Blog/app/Models/` |
| Migration | `Modules/Blog/database/migrations/` |
| Factory | `Modules/Blog/database/factories/` |
| Seeder | `Modules/Blog/database/seeders/` |
| Request | `Modules/Blog/app/Http/Requests/` |
| Resource | `Modules/Blog/app/Http/Resources/` |
| Policy | `Modules/Blog/app/Policies/` |
| Event | `Modules/Blog/app/Events/` |
| Listener | `Modules/Blog/app/Listeners/` |
| Observer | `Modules/Blog/app/Observers/` |
| Job | `Modules/Blog/app/Jobs/` |
| Mail | `Modules/Blog/app/Mail/` |
| Notification | `Modules/Blog/app/Notifications/` |
| Command | `Modules/Blog/app/Console/Commands/` |
| Provider | `Modules/Blog/app/Providers/` |
| Middleware | `Modules/Blog/app/Http/Middleware/` |
| Service | `Modules/Blog/app/Services/` |
| Repository | `Modules/Blog/app/Repositories/` |
| Action | `Modules/Blog/app/Actions/` |
| Test | `Modules/Blog/tests/Feature/` or `tests/Unit/` |
| Rule | `Modules/Blog/app/Rules/` |
| Enum | `Modules/Blog/app/Enums/` |
| Cast | `Modules/Blog/app/Casts/` |

All paths are configurable via `config/modules.php` under `paths.generator`.
