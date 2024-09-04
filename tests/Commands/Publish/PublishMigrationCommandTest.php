<?php

namespace Nwidart\Modules\Tests\Commands\Publish;

use Nwidart\Modules\Contracts\RepositoryInterface;
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

    public function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();
        $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog']);
    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        $this->finder->delete($this->finder->allFiles(base_path('database/migrations')));
        parent::tearDown();
    }

    public function test_it_publishes_module_migrations()
    {
        $code = $this->artisan('module:publish-migration', ['module' => 'Blog']);

        $files = $this->finder->allFiles(base_path('database/migrations'));

        $this->assertCount(1, $files);
        $this->assertSame(0, $code);
    }
}
