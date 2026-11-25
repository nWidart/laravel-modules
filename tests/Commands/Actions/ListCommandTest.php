<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\ModuleManifest;
use Nwidart\Modules\Tests\BaseTestCase;

class ListCommandTest extends BaseTestCase
{
    /**
     * @var string
     */
    private $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();
    }

    protected function tearDown(): void
    {
        // Delete the invalid module.json first: has() scans the modules again.
        $this->app['files']->deleteDirectory(base_path('modules/Broken'));
        $this->app['files']->deleteDirectory(base_path('extra-modules'));

        $repository = $this->app[RepositoryInterface::class]->resetModules();

        foreach (['Blog', 'Shop'] as $module) {
            if ($repository->has($module)) {
                $repository->delete($module);
            }
        }

        parent::tearDown();
    }

    public function test_it_can_list_modules()
    {
        $code = $this->artisan('module:list');

        // We just want to make sure nothing throws an exception inside the list command
        $this->assertTrue(true);
        $this->assertSame(0, $code);
    }

    public function test_it_lists_priority_zero_when_module_json_has_no_priority()
    {
        $path = $this->app[RepositoryInterface::class]->find('Blog')->getPath().'/module.json';
        $json = json_decode($this->app['files']->get($path), true);
        unset($json['priority']);
        $this->app['files']->put($path, json_encode($json));

        Artisan::call('module:list');

        $this->assertMatchesRegularExpression('/Blog .*modules\/Blog \[0\]/', Artisan::output());
    }

    public function test_it_lists_modules_by_priority_in_ascending_order_by_default()
    {
        $this->createModuleWithPriority('Shop', 1);
        $this->setModulePriority('Blog', 2);

        $code = $this->artisan('module:list', ['--only' => 'priority']);

        $this->assertSame(0, $code);
        $this->assertModulesListedInOrder(['Shop', 'Blog'], Artisan::output());
    }

    public function test_it_lists_modules_by_priority_in_descending_order()
    {
        $this->createModuleWithPriority('Shop', 1);
        $this->setModulePriority('Blog', 2);

        $code = $this->artisan('module:list', ['--only' => 'priority', '--direction' => 'desc']);

        $this->assertSame(0, $code);
        $this->assertModulesListedInOrder(['Blog', 'Shop'], Artisan::output());
    }

    public function test_it_lists_broken_modules_with_their_error()
    {
        $this->skipBrokenModules();

        $code = Artisan::call('module:list');
        $output = Artisan::output();

        $this->assertSame(0, $code);
        $this->assertMatchesRegularExpression('/\[Broken\] Blog[ .]+Provider class \[Modules\\\\Blog\\\\Providers\\\\MissingServiceProvider\] not found\./', $output);
        $this->assertMatchesRegularExpression('/\[Broken\] Broken[ .]+Error processing file: modules\/Broken\/module\.json\. Error: Syntax error/', $output);
        $this->assertStringNotContainsString('[Enabled] Blog', $output);
    }

    public function test_a_broken_module_json_is_left_out_when_filtering_by_status()
    {
        $this->skipBrokenModules();

        Artisan::call('module:list', ['--only' => 'enabled']);
        $output = Artisan::output();

        $this->assertStringContainsString('[Broken] Blog', $output);
        $this->assertStringNotContainsString('Broken] Broken', $output);
    }

    public function test_an_invalid_module_json_does_not_mark_a_module_with_its_folder_name_as_broken()
    {
        config(['modules.skip_broken_modules' => true]);
        $this->writeInvalidModuleJson('extra-modules/Blog');

        Artisan::call('module:list');
        $output = Artisan::output();

        $this->assertMatchesRegularExpression('/\[Enabled\] Blog[ .]+modules\/Blog \[0\]/', $output);
        $this->assertMatchesRegularExpression('/\[Broken\] extra-modules\/Blog[ .]+Error processing file: extra-modules\/Blog\/module\.json\. Error: Syntax error/', $output);
    }

    public function test_it_lists_a_broken_module_found_by_its_own_scan()
    {
        config(['modules.skip_broken_modules' => true]);
        $this->assertCount(1, $this->app[RepositoryInterface::class]->all());

        // A location added after the boot is scanned by module:list itself.
        $this->writeInvalidModuleJson('extra-modules/Shop');

        Artisan::call('module:list');

        $this->assertMatchesRegularExpression('/\[Broken\] Shop[ .]+Error processing file: extra-modules\/Shop\/module\.json/', Artisan::output());
    }

    /**
     * Write an invalid module.json in a folder of a location the repository scans too.
     */
    private function writeInvalidModuleJson(string $directory): void
    {
        $this->app['files']->ensureDirectoryExists(base_path($directory));
        $this->app['files']->put(base_path($directory.'/module.json'), '{ broken');
        $this->app[RepositoryInterface::class]->addLocation(dirname(base_path($directory)));
    }

    /**
     * Boot with 'skip_broken_modules': Blog lists a provider class that does not exist, Broken has an invalid module.json.
     */
    private function skipBrokenModules(): void
    {
        config(['modules.skip_broken_modules' => true]);
        $repository = $this->app[RepositoryInterface::class];
        $repository->find('Blog')->json()->set('providers', ['Modules\Blog\Providers\MissingServiceProvider'])->save();
        $this->app['files']->ensureDirectoryExists(base_path('modules/Broken'));
        $this->app['files']->put(base_path('modules/Broken/module.json'), '{ broken');
        $repository->resetModules();

        $this->app->forgetInstance(ModuleManifest::class);
        $this->app->make(ModuleManifest::class)->getProviders();
    }

    private function createModuleWithPriority(string $name, int $priority): void
    {
        $this->createModule($name);
        $this->setModulePriority($name, $priority);
    }

    private function setModulePriority(string $name, int $priority): void
    {
        $this->app[RepositoryInterface::class]->find($name)->json()->set('priority', $priority)->save();
    }

    private function assertModulesListedInOrder(array $expected, string $output): void
    {
        preg_match_all('/\] (Blog|Shop) /', $output, $matches);

        $this->assertSame($expected, $matches[1]);
    }
}
