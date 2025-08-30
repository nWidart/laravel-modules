<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class CommandMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $finder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_generates_a_new_console_command_class()
    {
        $code = $this->artisan('module:make-command', ['name' => 'MyAwesomeCommand', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->module_app_path('app/Console/MyAwesomeCommand.php')));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-command', ['name' => 'MyAwesomeCommand', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_app_path('app/Console/MyAwesomeCommand.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_uses_set_command_name_in_class()
    {
        $code = $this->artisan(
            'module:make-command',
            ['name' => 'MyAwesomeCommand', 'module' => 'Blog', '--command' => 'my:awesome']
        );

        $file = $this->finder->get($this->module_app_path('app/Console/MyAwesomeCommand.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_changes_the_default_path()
    {
        $this->app['config']->set('modules.paths.generator.command.path', 'app/CustomCommands');

        $code = $this->artisan('module:make-command', ['name' => 'AwesomeCommand', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_app_path('app/CustomCommands/AwesomeCommand.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.command.namespace', 'Commands');

        $code = $this->artisan('module:make-command', ['name' => 'AwesomeCommand', 'module' => 'Blog']);

        $file = $this->finder->get($this->module_app_path('app/Console/AwesomeCommand.php'));

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
