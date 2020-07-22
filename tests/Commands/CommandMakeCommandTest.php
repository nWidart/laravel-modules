<?php

namespace Nwidart\Modules\Tests\Commands;

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
    public function it_generates_a_new_console_command_class()
    {
        $code = $this->artisan('module:make-command', ['name' => 'MyAwesomeCommand', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Console/MyAwesomeCommand.php'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-command', ['name' => 'MyAwesomeCommand', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Console/MyAwesomeCommand.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_uses_set_command_name_in_class()
    {
        $code = $this->artisan(
            'module:make-command',
            ['name' => 'MyAwesomeCommand', 'module' => 'Blog', '--command' => 'my:awesome']
        );

        $file = $this->finder->get($this->modulePath . '/Console/MyAwesomeCommand.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.command.path', 'Commands');

        $code = $this->artisan('module:make-command', ['name' => 'AwesomeCommand', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Commands/AwesomeCommand.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_can_change_the_default_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.command.namespace', 'Commands');

        $code = $this->artisan('module:make-command', ['name' => 'AwesomeCommand', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Console/AwesomeCommand.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
