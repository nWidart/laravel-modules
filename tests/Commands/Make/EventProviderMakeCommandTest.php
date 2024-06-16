<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class EventProviderMakeCommandTest extends BaseTestCase
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
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();

    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_generates_a_new_event_provider_class()
    {
        $code = $this->artisan('module:make-event-provider', ['module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Providers/EventServiceProvider.php'));
        $this->assertSame(1, $code);
    }

    public function test_it_generates_a_new_event_provider_class_can_override_with_force_option()
    {
        $this->artisan('module:make-event-provider', ['module' => 'Blog']);
        $code = $this->artisan('module:make-event-provider', ['module' => 'Blog', '--force' => true]);

        $this->assertTrue(is_file($this->modulePath.'/Providers/EventServiceProvider.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-event-provider', ['module' => 'Blog', '--force' => true]);

        $file = $this->finder->get($this->modulePath.'/Providers/EventServiceProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
