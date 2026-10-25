<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ObserverMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

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
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_makes_observer()
    {
        $code = $this->artisan('module:make-observer', ['name' => 'Post', 'module' => 'Blog']);

        $observerFile = $this->modulePath.'/Observers/PostObserver.php';

        $this->assertTrue(is_file($observerFile), 'Observer file was not created.');
        $this->assertMatchesSnapshot($this->finder->get($observerFile));
        $this->assertSame(0, $code);
    }

    public function test_it_returns_an_error_when_the_observer_already_exists()
    {
        $this->artisan('module:make-observer', ['name' => 'Post', 'module' => 'Blog']);
        $code = $this->artisan('module:make-observer', ['name' => 'Post', 'module' => 'Blog']);

        $this->assertStringContainsString('already exists', Artisan::output());
        $this->assertSame(E_ERROR, $code);
    }
}
