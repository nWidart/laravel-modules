<?php

namespace Nwidart\Modules\Tests\Commands\Actions;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;
use Nwidart\Modules\Tests\BaseTestCase;

class DisableCommandTest extends BaseTestCase
{
    private RepositoryInterface $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');
        $this->createModule('Taxonomy');
        $this->repository = $this->app[RepositoryInterface::class];
    }

    public function tearDown(): void
    {
        $this->repository->delete('Blog');
        $this->repository->delete('Taxonomy');
        parent::tearDown();
    }

    public function test_it_disables_a_module()
    {
        /** @var Module $blogModule */
        $blogModule = $this->repository->find('Blog');
        $blogModule->disable();

        $code = $this->artisan('module:disable', ['module' => ['Blog']]);

        $this->assertTrue($blogModule->isDisabled());
        $this->assertSame(0, $code);
    }

    public function test_it_disables_array_of_modules()
    {
        /** @var Module $blogModule */
        $blogModule = $this->repository->find('Blog');
        $blogModule->enable();

        /** @var Module $taxonomyModule */
        $taxonomyModule = $this->repository->find('Taxonomy');
        $taxonomyModule->enable();

        $code = $this->artisan('module:disable', ['module' => ['Blog', 'Taxonomy']]);

        $this->assertTrue($blogModule->isDisabled() && $taxonomyModule->isDisabled());
        $this->assertSame(0, $code);
    }

    public function test_it_disables_all_modules()
    {
        /** @var Module $blogModule */
        $blogModule = $this->repository->find('Blog');
        $blogModule->enable();

        /** @var Module $taxonomyModule */
        $taxonomyModule = $this->repository->find('Taxonomy');
        $taxonomyModule->enable();

        $code = $this->artisan('module:disable', ['--all' => true]);

        $this->assertTrue($blogModule->isDisabled() && $taxonomyModule->isDisabled());
        $this->assertSame(0, $code);
    }
}
