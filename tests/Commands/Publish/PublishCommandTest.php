<?php

namespace Nwidart\Modules\Tests\Commands\Publish;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

class PublishCommandTest extends BaseTestCase
{
    /**
     * @var Filesystem
     */
    private $finder;

    /**
     * @var string
     */
    private $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule();
        $this->modulePath = $this->getModuleBasePath();
        $this->finder = $this->app['files'];
        $this->finder->put($this->modulePath.'/resources/assets/script.js', 'assetfile');
    }

    protected function tearDown(): void
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

    public function test_it_refreshes_published_assets_when_published_again()
    {
        $this->artisan('module:publish', ['module' => 'Blog']);
        $this->finder->put(public_path('modules/blog/script.js'), 'stale build');

        $this->mockConsoleOutput = true;
        $this->artisan('module:publish', ['module' => 'Blog'])
            ->doesntExpectOutputToContain('already exists')
            ->assertSuccessful();

        $this->assertSame('assetfile', $this->finder->get(public_path('modules/blog/script.js')));
    }
}
