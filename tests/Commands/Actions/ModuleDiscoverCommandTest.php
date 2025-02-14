<?php

namespace Commands\Actions;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\ModuleManifest;
use Nwidart\Modules\Tests\BaseTestCase;

/**
 * @deprecated This Test File is deprecated and will be removed in the next major version.
 */
class ModuleDiscoverCommandTest extends BaseTestCase
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

    public function test_run_command_without_error()
    {
        $this->createModule();

        $code = $this->artisan('module:discover');

        $this->assertSame(0, $code);
    }
}
