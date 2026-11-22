<?php

namespace Nwidart\Modules\Tests\Actuator;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Actuator\ActuatorServiceProvider;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\LaravelModulesServiceProvider;
use Nwidart\Modules\Tests\BaseTestCase;
use Orchestra\Testbench\Attributes\DefineEnvironment;

class ModulesEndpointTest extends BaseTestCase
{
    private const MODULES = ['BlogPost', 'Shop', 'analytics'];

    protected function getPackageProviders($app)
    {
        return [
            ActuatorServiceProvider::class,
            ...parent::getPackageProviders($app),
        ];
    }

    protected function tearDown(): void
    {
        $repository = $this->app[RepositoryInterface::class];

        foreach (self::MODULES as $name) {
            if ($repository->has($name)) {
                $repository->delete($name);
            }
        }

        parent::tearDown();
    }

    public function test_the_endpoint_is_not_exposed_by_default()
    {
        $this->createModules();

        $this->getJson('/actuator/modules')->assertNotFound();
        $this->getJson('/actuator/modules/Shop')->assertNotFound();

        $this->getJson('/actuator')
            ->assertOk()
            ->assertJsonPath('_links.health.href', 'http://localhost/actuator/health')
            ->assertJsonMissingPath('_links.modules')
            ->assertJsonMissingPath('_links.modules-name');
    }

    #[DefineEnvironment('exposeModules')]
    public function test_it_lists_every_module_sorted_by_name()
    {
        $this->createModules();

        $response = $this->getJson('/actuator/modules')->assertOk();

        $this->assertSame('nwidart/laravel-modules', $response->json('package.name'));
        $this->assertIsString($response->json('package.version'));
        $this->assertNotSame('', $response->json('package.version'));
        $this->assertSame(['name', 'version'], array_keys($response->json('package')));

        $this->assertSame([
            [
                'name' => 'analytics',
                'enabled' => true,
                'version' => null,
                'description' => '',
                'priority' => 0,
                'providers' => [],
            ],
            [
                'name' => 'BlogPost',
                'enabled' => true,
                'version' => '1.2.0',
                'description' => 'Blog posts and comments',
                'priority' => 0,
                'providers' => ['Modules\\BlogPost\\Providers\\BlogPostServiceProvider'],
            ],
            [
                'name' => 'Shop',
                'enabled' => false,
                'version' => null,
                'description' => '',
                'priority' => 10,
                'providers' => ['Modules\\Shop\\Providers\\ShopServiceProvider'],
            ],
        ], $response->json('modules'));
    }

    #[DefineEnvironment('exposeModules')]
    public function test_each_module_has_only_the_allowed_fields()
    {
        $this->createModules();

        $modules = $this->getJson('/actuator/modules')->assertOk()->json('modules');

        $this->assertCount(3, $modules);

        foreach ($modules as $module) {
            $this->assertSame(['name', 'enabled', 'version', 'description', 'priority', 'providers'], array_keys($module));
        }

        $this->assertStringNotContainsString(base_path(), json_encode($modules, JSON_UNESCAPED_SLASHES));
        $this->assertStringNotContainsString('do-not-expose', json_encode($modules));
    }

    #[DefineEnvironment('exposeModules')]
    public function test_it_shows_one_module_by_name()
    {
        $this->createModules();

        $blogPost = [
            'name' => 'BlogPost',
            'enabled' => true,
            'version' => '1.2.0',
            'description' => 'Blog posts and comments',
            'priority' => 0,
            'providers' => ['Modules\\BlogPost\\Providers\\BlogPostServiceProvider'],
        ];

        $this->assertSame($blogPost, $this->getJson('/actuator/modules/BlogPost')->assertOk()->json());
        $this->assertSame($blogPost, $this->getJson('/actuator/modules/blogpost')->assertOk()->json());
        $this->assertSame($blogPost, $this->getJson('/actuator/modules/blog-post')->assertOk()->json());
        $this->assertSame('Shop', $this->getJson('/actuator/modules/shop')->assertOk()->json('name'));
    }

    #[DefineEnvironment('exposeModules')]
    public function test_an_unknown_module_is_not_found()
    {
        $this->createModules();

        $this->getJson('/actuator/modules/Unknown')->assertNotFound();
    }

    #[DefineEnvironment('exposeModules')]
    public function test_discovery_links_the_endpoint_when_it_is_exposed()
    {
        $this->getJson('/actuator')
            ->assertOk()
            ->assertExactJson([
                '_links' => [
                    'self' => ['href' => 'http://localhost/actuator', 'templated' => false],
                    'modules' => ['href' => 'http://localhost/actuator/modules', 'templated' => false],
                    'modules-name' => ['href' => 'http://localhost/actuator/modules/{name}', 'templated' => true],
                ],
            ]);
    }

    #[DefineEnvironment('excludeModules')]
    public function test_an_excluded_endpoint_is_not_found()
    {
        $this->createModules();

        $this->getJson('/actuator/health')->assertOk();
        $this->getJson('/actuator/modules')->assertNotFound();
        $this->getJson('/actuator/modules/Shop')->assertNotFound();
        $this->getJson('/actuator')->assertOk()->assertJsonMissingPath('_links.modules');
    }

    #[DefineEnvironment('exposeModules')]
    public function test_registering_the_provider_again_does_not_register_the_endpoint_twice()
    {
        $this->app->register(LaravelModulesServiceProvider::class, true);

        $this->getJson('/actuator/modules')->assertOk()->assertJsonPath('modules', []);
    }

    /**
     * Testbench runs this after the providers register and before they boot, so before the actuator routes load.
     */
    protected function exposeModules($app): void
    {
        $app['config']->set('actuator.exposure.include', ['modules']);
    }

    protected function excludeModules($app): void
    {
        $app['config']->set('actuator.exposure.include', ['*']);
        $app['config']->set('actuator.exposure.exclude', ['modules']);
    }

    /**
     * Write the module.json of three modules: "BlogPost" and "analytics" enabled, "Shop" disabled.
     */
    private function createModules(): void
    {
        $this->writeModuleJson('BlogPost', [
            'name' => 'BlogPost',
            'alias' => 'blogpost',
            'description' => 'Blog posts and comments',
            'version' => '1.2.0',
            'keywords' => ['blog'],
            'priority' => 0,
            'providers' => ['Modules\\BlogPost\\Providers\\BlogPostServiceProvider'],
            'files' => ['do-not-expose.php'],
            'secret' => 'do-not-expose',
        ]);

        // The module:make stub: no version key.
        $this->writeModuleJson('Shop', [
            'name' => 'Shop',
            'alias' => 'shop',
            'description' => '',
            'keywords' => [],
            'priority' => '10',
            'providers' => ['Modules\\Shop\\Providers\\ShopServiceProvider'],
            'files' => [],
        ]);

        // Only a name, and an empty version.
        $this->writeModuleJson('analytics', [
            'name' => 'analytics',
            'version' => '',
        ]);

        $repository = $this->app[RepositoryInterface::class];
        $repository->resetModules();

        $repository->findOrFail('BlogPost')->enable();
        $repository->findOrFail('analytics')->enable();
        $repository->findOrFail('Shop')->disable();
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function writeModuleJson(string $name, array $json): void
    {
        $files = new Filesystem;
        $path = base_path("modules/{$name}");

        $files->ensureDirectoryExists($path);
        $files->put("{$path}/module.json", json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
