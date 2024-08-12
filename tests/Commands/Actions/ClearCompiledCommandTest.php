<?php

namespace Commands\Actions;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\ModuleManifest;
use Nwidart\Modules\Tests\BaseTestCase;

class ClearCompiledCommandTest extends BaseTestCase
{
    private RepositoryInterface $repository;

    private ?string $manifestPath;

    private Filesystem $finder;

    public function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->manifestPath = app()->make(ModuleManifest::class)->manifestPath;
        $this->repository = $this->app[RepositoryInterface::class];
    }

    public function tearDown(): void
    {
        $this->artisan('module:delete', ['--all' => true, '--force' => true]);
        parent::tearDown();
    }

    public function test_manifest_file_clear_when_call_command()
    {
        $this->createModule();
        $code = $this->artisan('module:clear-compiled');

        $this->assertFileDoesNotExist($this->manifestPath);
        $this->assertSame(0, $code);
    }

    public function test_manifest_file_clear_when_create_module()
    {
        $this->assertFileExists($this->manifestPath);

        $this->createModule('Foo');

        $this->assertFileDoesNotExist($this->manifestPath);
    }

    public function test_manifest_file_clear_when_delete_module()
    {
        $this->assertFileExists($this->manifestPath);

        $this->createModule('Foo');

        $this->artisan('module:delete', ['module' => 'Foo', '--force' => true]);

        $this->assertFileDoesNotExist($this->manifestPath);
    }

    public function test_manifest_file_clear_when_disable_module()
    {
        $this->assertFileExists($this->manifestPath);

        $this->createModule('Foo');

        $this->artisan('module:disable', ['module' => 'Foo']);

        $this->assertFileDoesNotExist($this->manifestPath);
    }

    public function test_manifest_file_clear_when_enable_module()
    {
        $this->assertFileExists($this->manifestPath);

        $this->createModule('Foo');

        $this->artisan('module:enable', ['module' => 'Foo']);

        $this->assertFileDoesNotExist($this->manifestPath);
    }
}
