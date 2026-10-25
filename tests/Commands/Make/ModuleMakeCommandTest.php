<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Nwidart\Modules\Constants\ModuleEvent;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Tests\Support\Fixtures\QuotedValueReplacement;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Exception\RuntimeException;

class ModuleMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    /**
     * @var Filesystem
     */
    private $finder;

    /**
     * @var string
     */
    private $modulePath;

    /**
     * @var ActivatorInterface
     */
    private $activator;

    /**
     * @var RepositoryInterface
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modulePath = $this->getModuleBasePath();
        $this->finder = $this->app['files'];
        $this->repository = $this->app[RepositoryInterface::class];
        $this->activator = $this->app[ActivatorInterface::class];
    }

    protected function tearDown(): void
    {
        $this->artisan('module:delete', ['--all' => true, '--force' => true]);
        $this->finder->deleteDirectory(base_path('custom-stubs'));

        $this->activator->reset();
        parent::tearDown();
    }

    public function test_it_generates_module()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertDirectoryExists($this->modulePath);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_module_folders()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        foreach (config('modules.paths.generator') as $directory) {
            $this->assertDirectoryExists($this->modulePath.'/'.$directory['path']);
        }
        $this->assertSame(0, $code);
    }

    public function test_it_generates_module_files()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        foreach (config('modules.stubs.files') as $file) {
            $path = base_path('modules/Blog').'/'.$file;
            $this->assertTrue($this->finder->exists($path), "[$file] does not exists");
        }
        $path = base_path('modules/Blog').'/module.json';
        $this->assertTrue($this->finder->exists($path), '[module.json] does not exists');
        $this->assertMatchesSnapshot($this->finder->get($path));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_web_route_file()
    {
        $files = $this->app['modules']->config('stubs.files');
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $path = $this->modulePath.'/'.$files['routes/web'];

        $this->assertMatchesSnapshot($this->finder->get($path));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_web_route_file_with_multi_segment_default_namespace()
    {
        $this->app['config']->set('modules.namespace', 'Custom\Modules');
        $files = $this->app['modules']->config('stubs.files');
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $path = $this->modulePath.'/'.$files['routes/web'];

        $this->assertMatchesSnapshot($this->finder->get($path));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_api_route_file()
    {
        $files = $this->app['modules']->config('stubs.files');
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $path = $this->modulePath.'/'.$files['routes/api'];

        $this->assertMatchesSnapshot($this->finder->get($path));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_api_route_file_with_multi_segment_default_namespace()
    {
        $this->app['config']->set('modules.namespace', 'Custom\Modules');
        $files = $this->app['modules']->config('stubs.files');

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $path = $this->modulePath.'/'.$files['routes/api'];

        $this->assertMatchesSnapshot($this->finder->get($path));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_vite_file()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $path = $this->modulePath.'/'.$this->app['modules']->config('stubs.files.vite');

        $this->assertMatchesSnapshot($this->finder->get($path));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_module_resources()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $path = $this->getModuleAppPath().'/Providers/BlogServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = $this->getModuleAppPath().'/Providers/EventServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = $this->getModuleAppPath().'/Providers/RouteServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = $this->getModuleAppPath().'/Http/Controllers/BlogController.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = $this->getModuleBasePath().'/database/seeders/BlogDatabaseSeeder.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $this->assertSame(0, $code);
    }

    public function test_it_generates_correct_composerjson_file()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $file = $this->finder->get($this->modulePath.'/composer.json');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_module_folder_using_studly_case()
    {
        $code = $this->artisan('module:make', ['name' => ['ModuleName']]);

        $this->assertTrue($this->finder->exists(base_path('modules/ModuleName')));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_module_namespace_using_studly_case()
    {
        $code = $this->artisan('module:make', ['name' => ['ModuleName']]);

        $file = $this->finder->get($this->getModuleAppPath('ModuleName').'/Providers/ModuleNameServiceProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_plain_module_with_no_resources()
    {
        $code = $this->artisan('module:make', ['name' => ['ModuleName'], '--plain' => true]);

        $path = base_path('modules/ModuleName').'/Providers/ModuleNameServiceProvider.php';
        $this->assertFalse($this->finder->exists($path));

        $path = base_path('modules/ModuleName').'/Http/Controllers/ModuleNameController.php';
        $this->assertFalse($this->finder->exists($path));

        $path = base_path('modules/ModuleName').'/Database/Seeders/ModuleNameDatabaseSeeder.php';
        $this->assertFalse($this->finder->exists($path));

        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_plain_module_with_no_files()
    {
        $code = $this->artisan('module:make', ['name' => ['ModuleName'], '--plain' => true]);

        foreach (config('modules.stubs.files') as $file) {
            $path = base_path('modules/ModuleName').'/'.$file;
            $this->assertFalse($this->finder->exists($path), "[$file] exists");
        }
        $path = base_path('modules/ModuleName').'/module.json';
        $this->assertTrue($this->finder->exists($path), '[module.json] does not exists');
        $this->assertSame(0, $code);
    }

    public function test_it_generates_plain_module_with_no_service_provider_in_modulejson_file()
    {
        $code = $this->artisan('module:make', ['name' => ['ModuleName'], '--plain' => true]);

        $path = base_path('modules/ModuleName').'/module.json';
        $content = json_decode($this->finder->get($path));

        $this->assertCount(0, $content->providers);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_plain_module_with_no_service_provider_when_provider_path_is_custom()
    {
        $this->app['config']->set('modules.paths.generator.provider', ['path' => 'app/Base/Providers', 'generate' => true]);

        $code = $this->artisan('module:make', ['name' => ['ModuleName'], '--plain' => true]);

        $path = base_path('modules/ModuleName').'/module.json';
        $content = json_decode($this->finder->get($path));

        $this->assertCount(0, $content->providers);
        $this->assertSame(0, $code);
    }

    public function test_it_outputs_error_when_module_exists()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $output = Artisan::output();
        $expected = 'ERROR  Module [Blog] already exists!';

        $this->assertTrue(Str::contains($output, $expected));

        $this->assertSame(E_ERROR, $code);
    }

    public function test_it_still_generates_module_if_it_exists_using_force_flag()
    {
        Event::fake();

        $this->artisan('module:make', ['name' => ['Blog']]);
        $code = $this->artisan('module:make', ['name' => ['Blog'], '--force' => true]);
        $this->assertSame(0, $code);

        Event::assertDispatched(sprintf('modules.%s.'.ModuleEvent::DELETING, strtolower('Blog')));
        Event::assertDispatched(sprintf('modules.%s.'.ModuleEvent::DELETED, strtolower('Blog')));
        Event::assertDispatched(sprintf('modules.%s.'.ModuleEvent::CREATED, strtolower('Blog')));
    }

    public function test_it_can_generate_module_with_old_config_format()
    {
        // The old format predates paths.app_folder: classes live at the module root.
        $this->app['config']->set('modules.paths.app_folder', '');
        $this->app['config']->set('modules.paths.generator', [
            'assets' => 'Assets',
            'config' => 'Config',
            'command' => 'Console',
            'event' => 'Events',
            'listener' => 'Listeners',
            'migration' => 'Database/Migrations',
            'factory' => 'Database/factories',
            'model' => 'Entities',
            'repository' => 'Repositories',
            'seeder' => 'Database/Seeders',
            'controller' => 'Http/Controllers',
            'filter' => 'Http/Middleware',
            'request' => 'Http/Requests',
            'provider' => 'Providers',
            'lang' => 'Resources/lang',
            'views' => 'Resources/views',
            'policies' => false,
            'rules' => false,
            'test' => 'Tests',
            'jobs' => 'Jobs',
            'emails' => 'Emails',
            'notifications' => 'Notifications',
            'resource' => false,
        ]);

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertDirectoryExists($this->modulePath.'/Assets');
        $this->assertDirectoryExists($this->modulePath.'/Emails');
        $this->assertFileDoesNotExist($this->modulePath.'/Rules');
        $this->assertFileDoesNotExist($this->modulePath.'/Policies');
        $this->assertSame(0, $code);
    }

    public function test_it_can_ignore_some_folders_to_generate_with_old_format()
    {
        $this->app['config']->set('modules.paths.generator.assets', false);
        $this->app['config']->set('modules.paths.generator.emails', false);

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertFileDoesNotExist($this->modulePath.'/Assets');
        $this->assertFileDoesNotExist($this->modulePath.'/Emails');
        $this->assertSame(0, $code);
    }

    public function test_it_can_ignore_some_folders_to_generate_with_new_format()
    {
        $this->app['config']->set('modules.paths.generator.assets', ['path' => 'Assets', 'generate' => false]);
        $this->app['config']->set('modules.paths.generator.emails', ['path' => 'app/Emails', 'generate' => false]);

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertFileDoesNotExist($this->modulePath.'/Assets');
        $this->assertFileDoesNotExist($this->modulePath.'/app/Emails');
        $this->assertSame(0, $code);
    }

    public function test_it_can_ignore_resource_folders_to_generate()
    {
        $this->app['config']->set('modules.paths.generator.seeder', ['path' => 'Database/Seeders', 'generate' => false]
        );
        $this->app['config']->set('modules.paths.generator.provider', ['path' => 'app/Providers', 'generate' => false]);
        $this->app['config']->set('modules.paths.generator.route-provider', ['path' => 'app/Providers', 'generate' => false]
        );
        $this->app['config']->set(
            'modules.paths.generator.controller',
            ['path' => 'app/Http/Controllers', 'generate' => false]
        );

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertFileDoesNotExist($this->modulePath.'/Database/Seeders');
        $this->assertFileDoesNotExist($this->modulePath.'/app/Providers');
        $this->assertFileDoesNotExist($this->modulePath.'/app/Http/Controllers');
        $this->assertSame(0, $code);
    }

    public function test_it_generates_enabled_module()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertTrue($this->repository->isEnabled('Blog'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_disabled_module_with_disabled_flag()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog'], '--disabled' => true]);

        $this->assertTrue($this->repository->isDisabled('Blog'));
        $this->assertSame(0, $code);
    }

    public function test_it_generes_module_with_new_provider_location()
    {
        $this->app['config']->set('modules.paths.generator.provider', ['path' => 'app/Base/Providers', 'generate' => true]);

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertDirectoryExists($this->modulePath.'/app/Base/Providers');
        $file = $this->finder->get($this->modulePath.'/module.json');
        $this->assertMatchesSnapshot($file);
        $file = $this->finder->get($this->modulePath.'/composer.json');
        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_web_module_with_resources()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog'], '--web' => true]);

        $path = $this->getModuleAppPath().'/Providers/BlogServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = $this->getModuleAppPath().'/Http/Controllers/BlogController.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = $this->getModuleBasePath().'/database/seeders/BlogDatabaseSeeder.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = $this->getModuleAppPath().'/Providers/RouteServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $this->assertSame(0, $code);
    }

    public function test_it_generates_api_module_with_resources()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog'], '--api' => true]);

        $path = $this->getModuleAppPath().'/Providers/BlogServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = $this->getModuleAppPath().'/Http/Controllers/BlogController.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = $this->getModuleBasePath().'/database/seeders/BlogDatabaseSeeder.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = $this->getModuleAppPath().'/Providers/RouteServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $this->assertSame(0, $code);
    }

    public function test_it_generates_web_module_with_resources_when_adding_more_than_one_option()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog'], '--api' => true, '--plain' => true]);

        $path = $this->getModuleAppPath().'/Providers/BlogServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = $this->getModuleAppPath().'/Http/Controllers/BlogController.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = $this->getModuleBasePath().'/database/seeders/BlogDatabaseSeeder.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $path = $this->getModuleAppPath().'/Providers/RouteServiceProvider.php';
        $this->assertTrue($this->finder->exists($path));
        $this->assertMatchesSnapshot($this->finder->get($path));

        $this->assertSame(0, $code);
    }

    public function test_it_generate_module_when_provider_is_enable_and_route_provider_is_enable()
    {
        $this->app['config']->set('modules.paths.generator.provider.generate', true);
        $this->app['config']->set('modules.paths.generator.route-provider.generate', true);

        $this->artisan('module:make', ['name' => ['Blog']]);

        $providerPath = $this->getModuleAppPath().'/Providers/BlogServiceProvider.php';
        $this->assertTrue($this->finder->exists($providerPath));
        $this->assertMatchesSnapshot($this->finder->get($providerPath));

        $RouteProviderPath = $this->getModuleAppPath().'/Providers/RouteServiceProvider.php';
        $this->assertTrue($this->finder->exists($RouteProviderPath));
        $this->assertMatchesSnapshot($this->finder->get($RouteProviderPath));

        $content = $this->finder->get($providerPath);

        $this->assertStringContainsString('RouteServiceProvider::class', $content);
    }

    public function test_it_generate_module_when_provider_is_enable_and_route_provider_is_disable()
    {
        $this->app['config']->set('modules.paths.generator.provider.generate', true);
        $this->app['config']->set('modules.paths.generator.route-provider.generate', false);

        $this->artisan('module:make', ['name' => ['Blog']]);

        $providerPath = $this->getModuleAppPath().'/Providers/BlogServiceProvider.php';
        $this->assertTrue($this->finder->exists($providerPath));
        $this->assertMatchesSnapshot($this->finder->get($providerPath));

        $RouteProviderPath = $this->getModuleAppPath().'/Providers/RouteServiceProvider.php';
        $this->assertTrue(! $this->finder->exists($RouteProviderPath));
        $this->assertTrue($this->finder->exists($this->getModuleAppPath().'/Providers/EventServiceProvider.php'));

        $content = $this->finder->get($providerPath);

        $this->assertStringNotContainsString('RouteServiceProvider::class', $content);
        $this->assertStringContainsString('EventServiceProvider::class', $content);
    }

    public function test_it_generate_module_when_provider_is_enable_and_event_provider_is_disable()
    {
        $this->app['config']->set('modules.paths.generator.provider.generate', true);
        $this->app['config']->set('modules.paths.generator.event-provider', ['path' => 'app/Providers', 'generate' => false]);

        $this->artisan('module:make', ['name' => ['Blog']]);

        $providerPath = $this->getModuleAppPath().'/Providers/BlogServiceProvider.php';
        $this->assertTrue($this->finder->exists($providerPath));
        $this->assertMatchesSnapshot($this->finder->get($providerPath));

        $this->assertFalse($this->finder->exists($this->getModuleAppPath().'/Providers/EventServiceProvider.php'));
        $this->assertTrue($this->finder->exists($this->getModuleAppPath().'/Providers/RouteServiceProvider.php'));

        $content = $this->finder->get($providerPath);

        $this->assertStringNotContainsString('EventServiceProvider::class', $content);
        $this->assertStringContainsString('RouteServiceProvider::class', $content);
    }

    public function test_it_generate_module_when_provider_is_enable_and_route_and_event_providers_are_disable()
    {
        $this->app['config']->set('modules.paths.generator.provider.generate', true);
        $this->app['config']->set('modules.paths.generator.route-provider.generate', false);
        $this->app['config']->set('modules.paths.generator.event-provider', ['path' => 'app/Providers', 'generate' => false]);

        $this->artisan('module:make', ['name' => ['Blog']]);

        $providerPath = $this->getModuleAppPath().'/Providers/BlogServiceProvider.php';
        $this->assertTrue($this->finder->exists($providerPath));
        $this->assertMatchesSnapshot($this->finder->get($providerPath));

        $this->assertFalse($this->finder->exists($this->getModuleAppPath().'/Providers/EventServiceProvider.php'));
        $this->assertFalse($this->finder->exists($this->getModuleAppPath().'/Providers/RouteServiceProvider.php'));

        $content = $this->finder->get($providerPath);

        $this->assertStringNotContainsString('EventServiceProvider::class', $content);
        $this->assertStringNotContainsString('RouteServiceProvider::class', $content);
        $this->assertStringContainsString('protected array $providers = [];', $content);
    }

    public function test_it_generate_module_when_provider_is_disable_and_route_provider_is_disable()
    {
        $this->app['config']->set('modules.paths.generator.provider.generate', false);
        $this->app['config']->set('modules.paths.generator.route-provider.generate', false);

        $this->artisan('module:make', ['name' => ['Blog']]);

        $providerPath = $this->getModuleAppPath().'/Providers/BlogServiceProvider.php';
        $this->assertTrue(! $this->finder->exists($providerPath));

        $RouteProviderPath = $this->getModuleAppPath().'/Providers/RouteServiceProvider.php';
        $this->assertTrue(! $this->finder->exists($RouteProviderPath));

        $content = $this->finder->get($this->getModuleBasePath().'/module.json');

        $this->assertStringNotContainsString('Modules\Blog\Providers\BlogServiceProvider', $content);
    }

    public function test_it_can_set_author_details()
    {
        $code = $this->artisan(
            'module:make',
            [
                'name' => ['Blog'],
                '--author-name' => 'Joe Blogs',
                '--author-email' => 'user@domain.com',
                '--author-vendor' => 'JoeBlogs',
            ]
        );

        $content = $this->finder->get($this->getModuleBasePath().'/composer.json');

        $this->assertStringContainsString('Joe Blogs', $content);
        $this->assertStringContainsString('user@domain.com', $content);
        $this->assertStringContainsString('JoeBlogs', $content);

        $this->assertSame(0, $code);
    }

    public function test_it_fires_events_when_module_created()
    {
        $module_name = 'Blog';
        Event::fake();

        $code = $this->createModule($module_name);

        $this->assertSame(0, $code);

        Event::assertDispatched(sprintf('modules.%s.'.ModuleEvent::CREATING, strtolower($module_name)));
        Event::assertDispatched(sprintf('modules.%s.'.ModuleEvent::CREATED, strtolower($module_name)));
    }

    public function test_it_generates_studly_module_and_fires_created_event_from_kebab_case_name()
    {
        Event::fake();

        $code = $this->artisan('module:make', ['name' => ['zz-probe-mod']]);

        $this->assertSame(0, $code);
        $this->assertDirectoryExists(base_path('modules/ZzProbeMod'));
        $this->assertDirectoryDoesNotExist(base_path('modules/zz-probe-mod'));
        $this->assertTrue($this->repository->has('ZzProbeMod'));

        Event::assertDispatched('modules.zzprobemod.'.ModuleEvent::CREATING);
        Event::assertDispatched('modules.zzprobemod.'.ModuleEvent::CREATED);
    }

    public function test_it_fires_events_when_multi_module_created()
    {
        Event::fake();

        $modules = [
            'Foo',
            'Bar',
            'Zoo',
        ];

        $code = $this->artisan('module:make', ['name' => $modules]);

        $this->assertSame(0, $code);

        foreach ($modules as $module) {
            Event::assertDispatched(sprintf('modules.%s.'.ModuleEvent::CREATING, strtolower($module)));
            Event::assertDispatched(sprintf('modules.%s.'.ModuleEvent::CREATED, strtolower($module)));
        }
    }

    public function test_it_fails_when_class_generator_paths_are_outside_the_app_folder()
    {
        $this->app['config']->set('modules.paths.app_folder', 'domain/');

        $code = $this->artisan('module:make', ['name' => ['Blog']]);
        $output = Artisan::output();

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString('Generator paths must be inside paths.app_folder [domain/]', $output);
        $this->assertStringContainsString('Modules\Blog\\', $output);
        $this->assertStringContainsString('model (app/Models)', $output);
        $this->assertStringContainsString('provider (app/Providers)', $output);
        $this->assertStringContainsString('controller (app/Http/Controllers)', $output);
        $this->assertStringContainsString('request (app/Http/Requests)', $output);
        $this->assertStringContainsString('Move these paths under domain/ in config/modules.php:', $output);
        // One offending key per line.
        $this->assertMatchesRegularExpression('#^\s*⇂ model \(app/Models\)\s*$#mu', $output);
        $this->assertMatchesRegularExpression('#^\s*⇂ provider \(app/Providers\)\s*$#mu', $output);
        $this->assertStringNotContainsString('seeder', $output);
        $this->assertStringNotContainsString('routes (', $output);
        $this->assertDirectoryDoesNotExist($this->modulePath);
        $this->assertFalse($this->repository->has('Blog'));
        $statusesFile = $this->activator->getStatusesFilePath();
        $statuses = $this->finder->exists($statusesFile) ? $this->finder->json($statusesFile) : [];
        $this->assertArrayNotHasKey('Blog', $statuses);
    }

    public function test_it_checks_class_generator_paths_with_an_explicit_namespace_or_generation_disabled()
    {
        $this->app['config']->set('modules.paths.app_folder', 'domain/');
        $this->moveClassGeneratorPathsTo('domain');
        $this->app['config']->set('modules.paths.generator.model', ['path' => 'app/Models', 'generate' => false]);
        $this->app['config']->set('modules.paths.generator.provider', ['path' => 'app/Providers', 'generate' => true, 'namespace' => 'Providers']);

        $code = $this->artisan('module:make', ['name' => ['Blog']]);
        $output = Artisan::output();

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString('model (app/Models)', $output);
        $this->assertStringContainsString('provider (app/Providers)', $output);
        $this->assertStringNotContainsString('controller (', $output);
        $this->assertDirectoryDoesNotExist($this->modulePath);
    }

    public function test_it_does_not_overwrite_an_existing_module_with_force_when_generator_paths_are_outside_the_app_folder()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);
        $this->app['config']->set('modules.paths.app_folder', 'domain/');

        $code = $this->artisan('module:make', ['name' => ['Blog'], '--force' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertFileExists($this->modulePath.'/app/Providers/BlogServiceProvider.php');
    }

    public function test_it_generates_module_when_class_generator_paths_are_inside_a_custom_app_folder()
    {
        $this->app['config']->set('modules.paths.app_folder', 'domain/');
        $this->moveClassGeneratorPathsTo('domain');

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertSame(0, $code);

        $provider = $this->modulePath.'/domain/Providers/BlogServiceProvider.php';
        $this->assertFileExists($provider);
        $this->assertStringContainsString('namespace Modules\Blog\Providers;', $this->finder->get($provider));
        $this->assertDirectoryDoesNotExist($this->modulePath.'/app');

        $composer = json_decode($this->finder->get($this->modulePath.'/composer.json'), true);
        $this->assertSame('domain/', $composer['autoload']['psr-4']['Modules\\Blog\\']);
    }

    public function test_it_does_not_check_generator_paths_when_the_app_folder_is_empty()
    {
        $this->app['config']->set('modules.paths.app_folder', '');

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertSame(0, $code);
        $this->assertFileExists($this->modulePath.'/app/Providers/BlogServiceProvider.php');
    }

    public function test_it_does_not_check_generator_paths_when_the_composer_stub_does_not_use_the_app_folder()
    {
        // An older published composer.stub that maps the module namespace to the module root.
        $stubsPath = base_path('stubs/legacy-composer-stub');
        $this->finder->ensureDirectoryExists($stubsPath);
        $this->finder->put(
            $stubsPath.'/composer.stub',
            str_replace('"$APP_FOLDER_NAME$"', '""', $this->finder->get(__DIR__.'/../../../src/Commands/stubs/composer.stub'))
        );
        Stub::setBasePath($stubsPath);
        $this->app['config']->set('modules.paths.generator.model', ['path' => 'Entities', 'generate' => true]);
        $this->app['config']->set('modules.paths.generator.controller', ['path' => 'Http/Controllers', 'generate' => true]);

        try {
            $code = $this->artisan('module:make', ['name' => ['Blog']]);
        } finally {
            $this->finder->deleteDirectory($stubsPath);
        }

        $this->assertSame(0, $code);
        $this->assertDirectoryExists($this->modulePath.'/Entities');
        $this->assertFileExists($this->modulePath.'/Http/Controllers/BlogController.php');

        $composer = json_decode($this->finder->get($this->modulePath.'/composer.json'), true);
        $this->assertSame('', $composer['autoload']['psr-4']['Modules\\Blog\\']);
    }

    public function test_it_does_not_check_generator_paths_when_no_composer_json_is_generated()
    {
        $files = $this->app['config']->get('modules.stubs.files');
        unset($files['composer']);
        $this->app['config']->set('modules.stubs.files', $files);
        $this->app['config']->set('modules.paths.generator.model', ['path' => 'Entities', 'generate' => true]);

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertSame(0, $code);
        $this->assertDirectoryExists($this->modulePath.'/Entities');
        $this->assertFileDoesNotExist($this->modulePath.'/composer.json');
    }

    public function test_it_does_not_check_generator_paths_for_a_plain_module()
    {
        $this->app['config']->set('modules.paths.generator.model', ['path' => 'Entities', 'generate' => true]);

        $code = $this->artisan('module:make', ['name' => ['Blog'], '--plain' => true]);

        $this->assertSame(0, $code);
        $this->assertDirectoryExists($this->modulePath.'/Entities');
    }

    /**
     * Move every "app/..." generator path under the given folder.
     */
    private function moveClassGeneratorPathsTo(string $folder): void
    {
        foreach ($this->app['config']->get('modules.paths.generator') as $key => $config) {
            if (is_array($config) && str_starts_with($config['path'], 'app/')) {
                $config['path'] = $folder.substr($config['path'], strlen('app'));
                $this->app['config']->set("modules.paths.generator.$key", $config);
            }
        }
    }

    public function test_it_fails_without_a_name_in_non_interactive_mode()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "name").');

        $this->artisan('module:make', ['--no-interaction' => true]);
    }

    public function test_it_prompts_for_the_name_in_interactive_mode()
    {
        $this->mockConsoleOutput = true;

        $this->artisan('module:make')
            ->expectsQuestion('What is the name of the module?', 'Blog')
            ->assertExitCode(0);

        $this->assertDirectoryExists($this->modulePath);
    }

    public static function invalidModuleNames(): array
    {
        return [
            'parent segment' => ['../Evil'],
            'nested path' => ['Evil/Nested'],
            'backslash' => ['..\\Evil'],
            'double dot' => ['Evil..'],
            'empty' => [''],
            'only a space' => [' '],
            'dot' => ['Foo.Bar'],
            'leading digit' => ['1Blog'],
        ];
    }

    #[DataProvider('invalidModuleNames')]
    public function test_it_rejects_module_names_that_are_not_valid_identifiers(string $name)
    {
        $before = $this->modulesFiles();

        $code = $this->artisan('module:make', ['name' => [$name]]);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString("The module name [{$name}] is not valid.", Artisan::output());
        $this->assertSame($before, $this->modulesFiles());
        $this->assertDirectoryDoesNotExist(base_path('Evil'));
        $this->assertFileDoesNotExist($this->activator->getStatusesFilePath());
    }

    public static function acceptedModuleNames(): array
    {
        return [
            'kebab' => ['zz-probe-mod', 'ZzProbeMod'],
            'snake' => ['snake_mod', 'SnakeMod'],
            'spaced' => ['Spaced Mod', 'SpacedMod'],
            'with digits' => ['Blog2', 'Blog2'],
        ];
    }

    #[DataProvider('acceptedModuleNames')]
    public function test_it_accepts_names_that_studly_case_to_valid_identifiers(string $name, string $studly)
    {
        $code = $this->artisan('module:make', ['name' => [$name]]);

        $this->assertSame(0, $code);
        $this->assertFileExists(base_path("modules/{$studly}/module.json"));
    }

    public function test_it_still_creates_valid_modules_next_to_a_rejected_name()
    {
        $code = $this->artisan('module:make', ['name' => ['../Evil', 'Blog']]);

        $this->assertSame(E_ERROR, $code);
        $this->assertDirectoryExists($this->modulePath);
        $this->assertDirectoryDoesNotExist(base_path('Evil'));
        $this->assertSame(['Blog' => true], json_decode($this->finder->get($this->activator->getStatusesFilePath()), true));
    }

    /**
     * @return list<string>
     */
    private function modulesFiles(): array
    {
        $modulesPath = base_path('modules');

        if (! $this->finder->isDirectory($modulesPath)) {
            return [];
        }

        return array_map(fn ($file) => $file->getPathname(), $this->finder->allFiles($modulesPath, true));
    }

    public function test_it_generates_valid_json_and_routes_with_a_nested_module_namespace()
    {
        $this->app['config']->set('modules.namespace', 'App\Modules');

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertSame(0, $code);

        $moduleJson = json_decode($this->finder->get($this->modulePath.'/module.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(['App\Modules\Blog\Providers\BlogServiceProvider'], $moduleJson['providers']);

        $composerJson = json_decode($this->finder->get($this->modulePath.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame([
            'App\Modules\Blog\\' => 'app/',
            'App\Modules\Blog\Database\Factories\\' => 'database/factories/',
            'App\Modules\Blog\Database\Seeders\\' => 'database/seeders/',
        ], $composerJson['autoload']['psr-4']);
        $this->assertSame(['App\Modules\Blog\Tests\\' => 'tests/'], $composerJson['autoload-dev']['psr-4']);

        foreach (['routes/web', 'routes/api'] as $stub) {
            $routes = $this->finder->get($this->modulePath.'/'.config('modules.stubs.files.'.$stub));

            $this->assertStringContainsString('use App\Modules\Blog\Http\Controllers\BlogController;', $routes);
            $this->assertStringNotContainsString('\\\\', $routes);
        }
    }

    public function test_it_escapes_replacements_by_output_format_in_published_stubs()
    {
        $this->app['config']->set('modules.namespace', 'App\Modules');
        $this->app['config']->set('modules.stubs.replacements.views/index', ['MODULE_NAMESPACE', 'STUDLY_NAME']);
        $this->app['config']->set('modules.stubs.replacements.package', ['MODULE_NAMESPACE', 'STUDLY_NAME']);

        $this->finder->ensureDirectoryExists(base_path('custom-stubs/views'));
        $this->finder->put(base_path('custom-stubs/views/index.stub'), '{{-- $MODULE_NAMESPACE$\$STUDLY_NAME$ --}}');
        $this->finder->put(base_path('custom-stubs/package.stub'), '{"namespace": "$MODULE_NAMESPACE$\\\\$STUDLY_NAME$"}');
        Stub::setBasePath(base_path('custom-stubs'));

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertSame(0, $code);

        $view = $this->finder->get($this->modulePath.'/'.config('modules.stubs.files.views/index'));
        $this->assertSame('{{-- App\Modules\Blog --}}', $view);

        $package = json_decode($this->finder->get($this->modulePath.'/package.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('App\Modules\Blog', $package['namespace']);
    }

    public function test_it_generates_a_plain_module_with_valid_json_and_no_providers()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog'], '--plain' => true]);

        $this->assertSame(0, $code);

        $content = $this->finder->get($this->modulePath.'/module.json');
        $json = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame([], $json['providers']);
        $this->assertSame('Blog', $json['name']);
        $this->assertStringContainsString('    "providers": [],', $content);
    }

    public function test_it_keeps_user_listed_providers_in_a_plain_module()
    {
        $this->app['config']->set('modules.paths.generator.provider', ['path' => 'Base/Providers', 'generate' => true]);

        $this->finder->ensureDirectoryExists(base_path('custom-stubs'));
        $this->finder->put(base_path('custom-stubs/json.stub'), <<<'STUB'
{
    "name": "$STUDLY_NAME$",
    "alias": "$LOWER_NAME$",
    "providers": [
        "$MODULE_NAMESPACE$\\$STUDLY_NAME$\\$PROVIDER_NAMESPACE$\\$STUDLY_NAME$ServiceProvider",
        "Acme\\Shared\\SharedServiceProvider"
    ],
    "files": []
}

STUB);
        Stub::setBasePath(base_path('custom-stubs'));

        $code = $this->artisan('module:make', ['name' => ['Blog'], '--plain' => true]);

        $this->assertSame(0, $code);

        $json = json_decode($this->finder->get($this->modulePath.'/module.json'), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(['Acme\Shared\SharedServiceProvider'], $json['providers']);
    }

    public function test_it_generates_valid_json_with_no_providers_when_provider_generation_is_disabled()
    {
        $this->app['config']->set('modules.paths.generator.provider.generate', false);

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertSame(0, $code);

        $content = $this->finder->get($this->modulePath.'/module.json');
        $json = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame([], $json['providers']);
        $this->assertSame('blog', $json['alias']);
    }

    public function test_it_generates_a_module_without_stub_warnings_for_the_default_config()
    {
        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertSame(0, $code);

        $output = Artisan::output();
        $this->assertStringNotContainsString('WARN', $output);
        $this->assertStringNotContainsString('ERROR', $output);
    }

    public function test_it_warns_about_an_unknown_replacement_key_and_still_generates_the_module()
    {
        $this->app['config']->set('modules.stubs.replacements.views/index', ['LOWER_NAME', 'NOT_A_REAL_KEY']);

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertSame(0, $code);
        $this->assertSame(1, substr_count(
            Artisan::output(),
            'Unknown replacement key [NOT_A_REAL_KEY] for stub [views/index]'
        ));
        $this->assertFileExists($this->modulePath.'/'.config('modules.stubs.files.views/index'));
    }

    public function test_it_warns_about_unreplaced_tokens_in_a_rendered_stub()
    {
        $this->app['config']->set('modules.stubs.replacements.views/master', ['STUDLY_NAME', 'KEBAB_NAME']);

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString(
            'Stub [views/master] still contains unreplaced tokens: $LOWER_NAME$',
            Artisan::output()
        );
    }

    public function test_it_fails_without_writing_when_a_custom_replacement_renders_invalid_composer_json()
    {
        $this->useQuotedReplacementFor('composer', 'VENDOR');

        $code = $this->artisan('module:make', ['name' => ['Blog']]);
        $output = Artisan::output();

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString('Stub [composer] renders invalid JSON for [composer.json]: Syntax error.', $output);
        $this->assertStringContainsString('ReplacementKeyCommand', $output);
        $this->assertStringContainsString('must be JSON-escaped', $output);
        $this->assertStringContainsString('Module [Blog] was not created.', $output);
        $this->assertModuleWasNotCreated('Blog');
    }

    public function test_it_fails_without_writing_when_a_custom_replacement_renders_invalid_module_json()
    {
        $this->useQuotedReplacementFor('json', 'LOWER_NAME');

        $code = $this->artisan('module:make', ['name' => ['Blog']]);
        $output = Artisan::output();

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString('Stub [json] renders invalid JSON for [module.json]: Syntax error.', $output);
        $this->assertModuleWasNotCreated('Blog');
    }

    public function test_it_fails_without_writing_for_a_plain_module_with_an_invalid_json_stub()
    {
        $this->useQuotedReplacementFor('json', 'LOWER_NAME');

        $code = $this->artisan('module:make', ['name' => ['Blog'], '--plain' => true]);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString('Stub [json] renders invalid JSON for [module.json]', Artisan::output());
        $this->assertModuleWasNotCreated('Blog');
    }

    public function test_it_leaves_an_existing_module_untouched_with_force_when_a_json_stub_is_invalid()
    {
        $this->artisan('module:make', ['name' => ['Blog']]);
        $composer = $this->finder->get($this->modulePath.'/composer.json');
        $moduleJson = $this->finder->get($this->modulePath.'/module.json');

        $this->useQuotedReplacementFor('composer', 'VENDOR');
        $code = $this->artisan('module:make', ['name' => ['Blog'], '--force' => true]);

        $this->assertSame(E_ERROR, $code);
        $output = Artisan::output();
        $this->assertStringContainsString('Stub [composer] renders invalid JSON', $output);
        $this->assertStringContainsString('Module [Blog] was not overwritten.', $output);
        $this->assertStringNotContainsString('was not created', $output);
        $this->assertSame($composer, $this->finder->get($this->modulePath.'/composer.json'));
        $this->assertSame($moduleJson, $this->finder->get($this->modulePath.'/module.json'));
        $this->assertFileExists($this->getModuleAppPath().'/Providers/BlogServiceProvider.php');
        $this->assertTrue($this->repository->has('Blog'));
    }

    public function test_it_does_not_fire_the_creating_event_when_a_json_stub_is_invalid()
    {
        Event::fake();
        $this->useQuotedReplacementFor('composer', 'VENDOR');

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertSame(E_ERROR, $code);
        Event::assertNotDispatched('modules.blog.'.ModuleEvent::CREATING);
    }

    public function test_it_does_not_run_composer_dump_autoload_when_no_module_was_generated()
    {
        Process::fake();
        $this->useQuotedReplacementFor('composer', 'VENDOR');

        $code = $this->artisan('module:make', ['name' => ['Blog']]);

        $this->assertSame(E_ERROR, $code);
        $this->assertStringNotContainsString('composer dump-autoload failed', Artisan::output());
        Process::assertNothingRan();
    }

    public function test_it_runs_composer_dump_autoload_when_at_least_one_module_was_generated()
    {
        Process::fake();

        $code = $this->artisan('module:make', ['name' => ['Blog', '../Evil']]);

        $this->assertSame(E_ERROR, $code);
        $this->assertTrue($this->repository->has('Blog'));
        Process::assertRan('composer dump-autoload');
    }

    public function test_it_fails_when_composer_dump_autoload_fails()
    {
        Process::fake([
            '*dump-autoload*' => Process::result(
                errorOutput: "\nIn JsonFile.php line 382:\n\n  \"./Modules/Blog/composer.json\" does not contain valid JSON\n  Parse error on line 2\n\n\ndump-autoload [-o|--optimize] [-a|--classmap-authoritative] [--dev] [--no-dev]\n\n",
                exitCode: 1,
            ),
        ]);

        $code = $this->artisan('module:make', ['name' => ['Blog']]);
        $output = Artisan::output();

        $this->assertSame(E_ERROR, $code);
        $this->assertStringContainsString('composer dump-autoload failed.', $output);
        // Composer's own lines are kept, one per line, without the trailing usage synopsis.
        $this->assertStringContainsString("In JsonFile.php line 382:\n\n  \"./Modules/Blog/composer.json\" does not contain valid JSON\n  Parse error on line 2\n", $output);
        $this->assertStringNotContainsString('[-o|--optimize]', $output);
        $this->assertStringContainsString('run [composer dump-autoload]', $output);
        Process::assertRan('composer dump-autoload');
    }

    /**
     * Map the given token of a stub to QuotedValueReplacement, which returns an unescaped `"`.
     */
    private function useQuotedReplacementFor(string $stub, string $key): void
    {
        $keys = array_values(array_diff(config("modules.stubs.replacements.$stub"), [$key]));
        $this->app['config']->set("modules.stubs.replacements.$stub", $keys + [$key => QuotedValueReplacement::class]);
    }

    private function assertModuleWasNotCreated(string $name): void
    {
        $this->assertDirectoryDoesNotExist(base_path("modules/$name"));
        $this->assertFalse($this->repository->has($name));

        $statusesFile = $this->activator->getStatusesFilePath();
        $statuses = $this->finder->exists($statusesFile) ? $this->finder->json($statusesFile) : [];
        $this->assertArrayNotHasKey($name, $statuses);
    }
}
