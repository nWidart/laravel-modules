<?php

namespace Commands\Actions;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\ModuleManifest;
use Nwidart\Modules\Tests\BaseTestCase;

class ModuleDiscoverCommandTest extends BaseTestCase
{
    private RepositoryInterface $repository;

    private ?string $manifestPath;

    private Filesystem $finder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->manifestPath = app()->make(ModuleManifest::class)->manifestPath;
        $this->repository = $this->app[RepositoryInterface::class];
    }

    protected function tearDown(): void
    {
        $this->artisan('module:delete', ['--all' => true, '--force' => true]);
        parent::tearDown();
    }

    public function test_run_command_without_error()
    {
        $this->createModule();

        $code = $this->artisan('module:discover');

        $this->assertSame(0, $code);
    }

    public function test_manifest_file_contain_new_module_provider()
    {
        $this->createModule('Foo');

        $path = base_path('modules/Foo').'/module.json';
        $provider = json_decode($this->finder->get($path))->providers[0];

        $code = $this->artisan('module:discover');
        $this->assertSame(0, $code);

        $manifest = require $this->manifestPath;

        $this->assertContains($provider, $manifest['providers'], 'provider not found in manifest file');
        $this->assertContains($provider, $manifest['eager'], 'provider not found in manifest file');
    }

    public function test_manifest_file_contain_multi_module_provider()
    {
        $modules = [
            'Foo',
            'Bar',
            'Baz',
        ];

        foreach ($modules as $module) {
            $this->createModule($module);
        }

        $code = $this->artisan('module:discover');
        $this->assertSame(0, $code);

        $manifest = require $this->manifestPath;

        foreach ($modules as $module) {
            $path = module_path($module).'/module.json';
            $provider = json_decode($this->finder->get($path))->providers[0];

            $this->assertContains($provider, $manifest['providers'], 'provider not found in manifest file');
            $this->assertContains($provider, $manifest['eager'], 'provider not found in manifest file');
        }
    }
}
