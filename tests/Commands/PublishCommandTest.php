<?php

namespace Nwidart\Modules\tests\Commands;

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

    public function setUp()
    {
        parent::setUp();
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog']]);
        $this->finder->put($this->modulePath . '/Assets/script.js', 'assetfile');
    }

    public function tearDown()
    {
        $this->finder->deleteDirectory($this->modulePath);
        parent::tearDown();
    }

    /** @test */
    public function it_published_module_assets()
    {
        $this->artisan('module:publish', ['module' => 'Blog']);

        $this->assertTrue(is_file(public_path('modules/blog/script.js')));
    }
}
