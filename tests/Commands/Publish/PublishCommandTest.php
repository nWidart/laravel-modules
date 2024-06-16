<?php

namespace Nwidart\Modules\Tests\Commands\Publish;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

class PublishCommandTest extends BaseTestCase
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
        $this->createModule();
        $this->modulePath = $this->getModuleBasePath();
        $this->finder = $this->app['files'];
        $this->finder->put($this->modulePath.'/resources/assets/script.js', 'assetfile');
    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_published_module_assets()
    {
        $code = $this->artisan('module:publish', ['module' => 'Blog']);

        $this->assertTrue(is_file(public_path('modules/blog/script.js')));
        $this->assertSame(0, $code);
    }
}
