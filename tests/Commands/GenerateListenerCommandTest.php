<?php

namespace Nwidart\Modules\tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;

class GenerateListenerCommandTest extends BaseTestCase
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
    }

    public function tearDown()
    {
        $this->finder->deleteDirectory($this->modulePath);
        parent::tearDown();
    }

    /** @test */
    public function it_generates_a_new_event_class()
    {
        $this->artisan('module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'UserWasCreated']);

        $this->assertTrue(is_file($this->modulePath . '/Events/Handlers/NotifyUsersOfANewPost.php'));
    }

    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $this->artisan('module:make-listener',
            ['name' => 'NotifyUsersOfANewPost', 'module' => 'Blog', '--event' => 'UserWasCreated']);

        $file = $this->finder->get($this->modulePath . '/Events/Handlers/NotifyUsersOfANewPost.php');

        $this->assertEquals($this->expectedContent(), $file);
    }

    private function expectedContent()
    {
        $event = '$event';

        return <<<TEXT
<?php

namespace Modules\Blog\Events\Handlers;

use Modules\Blog\Events\UserWasCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyUsersOfANewPost
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param \Modules\Blog\Events\UserWasCreated $event
     * @return void
     */
    public function handle(\Modules\Blog\Events\UserWasCreated $event)
    {
        //
    }
}

TEXT;
    }
}
