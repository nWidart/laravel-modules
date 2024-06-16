<?php

namespace Nwidart\Modules\Tests\Commands\Actions;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;
use Nwidart\Modules\Tests\BaseTestCase;

class EnableCommandTest extends BaseTestCase
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

    public function test_it_enables_a_module()
    {
        /** @var Module $blogModule */
        $blogModule = $this->repository->find('Blog');
        $blogModule->disable();

        $code = $this->artisan('module:enable', ['module' => 'Blog']);

        $this->assertTrue($blogModule->isEnabled());
        $this->assertSame(0, $code);
    }

    public function test_it_enables_array_of_modules()
    {
        /** @var Module $blogModule */
        $blogModule = $this->repository->find('Blog');
        $blogModule->disable();

        /** @var Module $taxonomyModule */
        $taxonomyModule = $this->repository->find('Taxonomy');
        $taxonomyModule->disable();

        $code = $this->artisan('module:enable', ['module' => ['Blog', 'Taxonomy']]);

        $this->assertTrue($blogModule->isEnabled() && $taxonomyModule->isEnabled());
        $this->assertSame(0, $code);
    }

    public function test_it_enables_all_modules()
    {
        /** @var Module $blogModule */
        $blogModule = $this->repository->find('Blog');
        $blogModule->disable();

        /** @var Module $taxonomyModule */
        $taxonomyModule = $this->repository->find('Taxonomy');
        $taxonomyModule->disable();

        $code = $this->artisan('module:enable', ['--all' => true]);

        $this->assertTrue($blogModule->isEnabled() && $taxonomyModule->isEnabled());
        $this->assertSame(0, $code);
    }
}
