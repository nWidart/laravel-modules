# Testing Laravel Modules

## Test Location

Tests live inside the module directory itself:

```
Modules/Blog/
└── tests/
    ├── Feature/
    │   └── PostFeatureTest.php
    └── Unit/
        └── PostTest.php
```

## Generating Tests

```bash
php artisan module:make-test PostFeatureTest Blog         # Feature test
php artisan module:make-test PostUnitTest Blog --unit     # Unit test
```

## Running Module Tests

```bash
# Run all tests for a specific module
php artisan test --compact Modules/Blog/tests/

# Run a specific test class
php artisan test --compact --filter=PostFeatureTest

# Run a specific test
php artisan test --compact --filter="can view a published post"

# Run all module tests (across all modules)
php artisan test --compact Modules/
```

## Feature Test Pattern

```php
// Modules/Blog/tests/Feature/PostFeatureTest.php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can view a published post', function () {
    $post = \Modules\Blog\Models\Post::factory()->published()->create();

    $this->get(route('blog.posts.show', $post))
        ->assertOk()
        ->assertSee($post->title);
});

it('redirects guests from protected routes', function () {
    $this->get(route('blog.posts.create'))
        ->assertRedirect(route('login'));
});

it('can create a post', function () {
    $user = \App\Models\User::factory()->create();

    $this->actingAs($user)
        ->post(route('blog.posts.store'), [
            'title' => 'Hello World',
            'body'  => 'Content here.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('posts', ['title' => 'Hello World']);
});
```

## Unit Test Pattern

```php
// Modules/Blog/tests/Unit/PostTest.php
use Modules\Blog\Models\Post;

uses(Tests\TestCase::class);

it('generates a slug from the title', function () {
    $post = new Post(['title' => 'Hello World']);

    expect($post->slug)->toBe('hello-world');
});
```

## Module Factories

Factories are located in `Modules/Blog/database/factories/`. Reference them via the fully-qualified model class:

```php
use Modules\Blog\Models\Post;

// In tests
$post = Post::factory()->create();
$post = Post::factory()->published()->create(['title' => 'Override']);
$posts = Post::factory()->count(5)->create();
```

```php
// Modules/Blog/database/factories/PostFactory.php
namespace Modules\Blog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Blog\Models\Post;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title'        => $this->faker->sentence(),
            'body'         => $this->faker->paragraphs(3, true),
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['published_at' => now()]);
    }
}
```

## Testing Module-Specific Behaviour

### Test Module Is Enabled/Disabled

```php
use Nwidart\Modules\Facades\Module;

it('Blog module is enabled', function () {
    expect(Module::isEnabled('Blog'))->toBeTrue();
});
```

### Test Config Loaded from Module

```php
it('loads module config', function () {
    expect(config('blog.posts_per_page'))->toBe(15);
});
```

### Test Module Views

```php
it('renders the blog index view', function () {
    $this->get(route('blog.posts.index'))
        ->assertOk()
        ->assertViewIs('blog::posts.index');
});
```

### Test Module Events

```php
use Modules\Blog\Events\PostPublished;
use Illuminate\Support\Facades\Event;

it('fires PostPublished when a post is published', function () {
    Event::fake([PostPublished::class]);

    $post = \Modules\Blog\Models\Post::factory()->create();
    $post->publish();

    Event::assertDispatched(PostPublished::class, fn ($event) => $event->post->is($post));
});
```

### Test Module Jobs

```php
use Modules\Blog\Jobs\ProcessPost;
use Illuminate\Support\Facades\Queue;

it('dispatches ProcessPost on creation', function () {
    Queue::fake();

    \Modules\Blog\Models\Post::factory()->create();

    Queue::assertPushed(ProcessPost::class);
});
```

## Test Database Isolation

Use `RefreshDatabase` or `LazilyRefreshDatabase` (faster for large test suites):

```php
// Recommended for module test suites
uses(Tests\TestCase::class, Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);
```

## PHPUnit Configuration

To ensure module tests are discovered, verify your `phpunit.xml` or `phpunit.xml.dist` includes the `Modules/` directory:

```xml
<testsuites>
    <testsuite name="Feature">
        <directory suffix="Test.php">./tests/Feature</directory>
        <directory suffix="Test.php">./Modules</directory>
    </testsuite>
    <testsuite name="Unit">
        <directory suffix="Test.php">./tests/Unit</directory>
    </testsuite>
</testsuites>
```

Or use Pest's `--path` option when running module-specific tests:

```bash
php artisan test --compact --path=Modules/Blog/tests
```
