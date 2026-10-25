<?php

namespace Nwidart\Modules\Tests\Commands;

use Illuminate\Contracts\Console\Kernel;
use Nwidart\Modules\Tests\BaseTestCase;

class SetupCommandTest extends BaseTestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = base_path('setup-command-test');
        $this->app['config']->set('modules.paths.modules', $this->root.'/Modules');
        $this->app['config']->set('modules.paths.assets', $this->root.'/public/modules');
    }

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_it_creates_the_modules_and_assets_folders()
    {
        $code = $this->artisan('module:setup');
        $output = $this->app[Kernel::class]->output();

        $this->assertSame(0, $code);
        $this->assertDirectoryExists($this->root.'/Modules');
        $this->assertDirectoryExists($this->root.'/public/modules');
        $this->assertStringContainsString('Modules directory created successfully', $output);
        $this->assertStringContainsString('Assets directory created successfully', $output);
    }

    public function test_it_succeeds_when_the_folders_already_exist()
    {
        $this->app['files']->ensureDirectoryExists($this->root.'/Modules');
        $this->app['files']->ensureDirectoryExists($this->root.'/public/modules');

        $code = $this->artisan('module:setup');
        $output = $this->app[Kernel::class]->output();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Modules directory already exists', $output);
        $this->assertStringContainsString('Assets directory already exists', $output);
        $this->assertStringNotContainsString('ERROR', $output);
    }
}
