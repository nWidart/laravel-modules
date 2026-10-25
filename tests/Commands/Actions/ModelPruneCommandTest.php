<?php

namespace Nwidart\Modules\Tests\Commands\Actions;

use Illuminate\Contracts\Console\Kernel;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

class ModelPruneCommandTest extends BaseTestCase
{
    private RepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');
        $this->repository = $this->app[RepositoryInterface::class];
        $this->app['files']->ensureDirectoryExists(base_path('modules/Blog/app/Models'));
    }

    protected function tearDown(): void
    {
        $this->repository->delete('Blog');
        parent::tearDown();
    }

    public function test_it_checks_all_modules_with_all_without_interaction()
    {
        $code = $this->artisan('module:prune', ['--all' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('No prunable models found.', $this->app[Kernel::class]->output());
    }
}
