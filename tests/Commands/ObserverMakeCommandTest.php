<?php

namespace Nwidart\Modules\Tests\Commands;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ObserverMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;
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
        $this->modulePath = base_path('modules/Blog');
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    /** @test */
    public function it_makes_observer()
    {
        $code = $this->artisan('module:make-observer', ['name' => 'Post', 'module' => 'Blog']);

        $observerFile = $this->modulePath . '/Observers/PostObserver.php';
        // dd($observerFile);

        $this->assertTrue(is_file($observerFile), 'Observer file was not created.');
        $this->assertMatchesSnapshot($this->finder->get($observerFile));
        $this->assertSame(0, $code);
    }
}
