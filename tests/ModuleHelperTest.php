<?php

namespace Nwidart\Modules\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
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

    public function test_module_returns_true_when_found()
    {
        $this->assertTrue(module('Blog'));
    }

    public function test_module_returns_false_and_log_error_when_not_found()
    {
        Log::shouldReceive('error')->once()->with("Module 'Blogs' not found.");

        $this->assertFalse(module('Blogs'));
    }

    public function test_module_returns_false_and_log_error_when_not_found_and_instance_parameter_is_true()
    {
        Log::shouldReceive('error')->once()->with("Module 'Blogs' not found.");

        $this->assertFalse(module('Blogs', true));
    }

    public function test_module_returns_instance_when_instance_parameter_is_true()
    {
        $module = module('Blog', true);

        $this->assertInstanceOf(Module::class, $module);
        $this->assertEquals('Blog', $module->getName());
    }

    public function test_module_returns_false_when_disabled()
    {
        Artisan::call('module:disable Blog');

        $this->assertFalse(module('Blog'));
    }

    public function test_module_returns_instance_when_disabled_and_instance_parameter_is_true()
    {
        Artisan::call('module:disable Blog');

        $module = module('Blog', true);

        $this->assertInstanceOf(Module::class, $module);
        $this->assertEquals('Blog', $module->getName());
    }

    public function test_module_directive_renders_content_when_module_is_enabled()
    {
        $blade = "@module('Blog') Enabled @endmodule";

        $this->assertStringContainsString('Enabled', Blade::render($blade));
    }

    public function test_module_directive_does_not_render_content_when_module_is_disabled()
    {
        Artisan::call('module:disable Blog');

        $blade = "@module('Blog') Enabled @endmodule";

        $this->assertStringNotContainsString('Enabled', Blade::render($blade));
    }
}
