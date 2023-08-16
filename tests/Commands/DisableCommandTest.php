<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;
use Nwidart\Modules\Tests\BaseTestCase;

class DisableCommandTest extends BaseTestCase
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
    public function it_disables_a_module()
    {
        /** @var Module $blogModule */
        $blogModule = $this->app[RepositoryInterface::class]->find('Blog');
        $blogModule->disable();

        $code = $this->artisan('module:disable', ['module' => ['Blog']]);

        $this->assertTrue($blogModule->isDisabled());
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_disables_array_of_modules()
    {
        /** @var Module $blogModule */
        $blogModule = $this->app[RepositoryInterface::class]->find('Blog');
        $blogModule->enable();

        /** @var Module $taxonomyModule */
        $taxonomyModule = $this->app[RepositoryInterface::class]->find('Taxonomy');
        $taxonomyModule->enable();

        $code = $this->artisan('module:disable',['module' => ['Blog','Taxonomy']]);

        $this->assertTrue($blogModule->isDisabled() && $taxonomyModule->isDisabled());
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_disables_all_modules()
    {
        /** @var Module $blogModule */
        $blogModule = $this->app[RepositoryInterface::class]->find('Blog');
        $blogModule->enable();

        /** @var Module $taxonomyModule */
        $taxonomyModule = $this->app[RepositoryInterface::class]->find('Taxonomy');
        $taxonomyModule->enable();

        $code = $this->artisan('module:disable');

        $this->assertTrue($blogModule->isDisabled() && $taxonomyModule->isDisabled());
        $this->assertSame(0, $code);
    }
}
