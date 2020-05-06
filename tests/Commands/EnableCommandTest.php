<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;
use Nwidart\Modules\Tests\BaseTestCase;

class EnableCommandTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('module:make', ['name' => ['Blog']]);
        $this->artisan('module:make', ['name' => ['Taxonomy']]);
    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        $this->app[RepositoryInterface::class]->delete('Taxonomy');
        parent::tearDown();
    }

    /** @test */
    public function it_enables_a_module()
    {
        /** @var Module $blogModule */
        $blogModule = $this->app[RepositoryInterface::class]->find('Blog');
        $blogModule->disable();

        $this->artisan('module:enable', ['module' => 'Blog']);

        $this->assertTrue($blogModule->isEnabled());
    }

    /** @test */
    public function it_enables_all_modules()
    {
        /** @var Module $blogModule */
        $blogModule = $this->app[RepositoryInterface::class]->find('Blog');
        $blogModule->disable();

        /** @var Module $taxonomyModule */
        $taxonomyModule = $this->app[RepositoryInterface::class]->find('Taxonomy');
        $taxonomyModule->disable();

        $this->artisan('module:enable');

        $this->assertTrue($blogModule->isEnabled() && $taxonomyModule->isEnabled());
    }
}
