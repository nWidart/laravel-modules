<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;

class PublishMigrationCommandTest extends BaseTestCase
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;
    /**
     * @var string
     */
    private $modulePath;

    public function setUp()
    {
        parent::setUp();
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog']]);
        $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog']);
    }

    public function tearDown()
    {
        $this->finder->deleteDirectory($this->modulePath);
        $this->finder->delete($this->finder->allFiles(base_path('database/migrations')));
        parent::tearDown();
    }

    /** @test */
    public function it_publishes_module_migrations()
    {
        $this->artisan('module:publish-migration', ['module' => 'Blog']);

        $files = $this->finder->allFiles(base_path('database/migrations'));

        $this->assertCount(1, $files);
    }
}
