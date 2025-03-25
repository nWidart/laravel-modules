<?php

namespace Nwidart\Modules\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Laravel\Module;

class ModuleHelperTest extends BaseTestCase
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;

    /**
     * @var string
     */
    private $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_module_returns_instance_when_exists()
    {
        $module = module('Blog');

        $this->assertInstanceOf(Module::class, $module);
        $this->assertEquals('Blog', $module->getName());
    }

    public function test_module_returns_false_when_not_found()
    {
        Log::shouldReceive('error')->once()->with("Module 'Blogs' not found.");

        $this->assertFalse(module('Blogs'));
    }

    public function test_module_returns_false_when_not_found_and_status_parameter_is_true()
    {
        Log::shouldReceive('error')->once()->with("Module 'Blogs' not found.");

        $this->assertFalse(module('Blogs'));
    }

    public function test_module_returns_status_when_status_parameter_is_true()
    {
        $this->assertTrue(module('Blog', true));
    }

    public function test_module_returns_status_when_status_parameter_is_true_and_module_is_disabled()
    {
        Artisan::call('module:disable Blog');
        $this->assertFalse(module('Blog', true));
    }
}
