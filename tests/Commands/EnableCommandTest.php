<?php

namespace Nwidart\Modules\Tests\Commands;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;
use Nwidart\Modules\Tests\BaseTestCase;

class EnableCommandTest extends BaseTestCase
{
    /**
     * @var Filesystem
     */
    private $finder;
    /**
     * @var string
     */
    private $modulePath;

    public function setUp(): void
    {
        parent::setUp();
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    public function tearDown(): void
    {
        $this->finder->deleteDirectory($this->modulePath);
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

}
