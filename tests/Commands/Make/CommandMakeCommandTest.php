<?php

namespace Nwidart\Modules\Tests\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class CommandMakeCommandTest extends BaseTestCase
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

    public function test_it_generates_a_new_console_command_class()
    {
        $code = $this->artisan('module:make-command', ['name' => 'MyAwesomeCommand', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/Console/MyAwesomeCommand.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content()
    {
        $code = $this->artisan('module:make-command', ['name' => 'MyAwesomeCommand', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Console/MyAwesomeCommand.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_uses_set_command_name_in_class()
    {
        $code = $this->artisan(
            'module:make-command',
            ['name' => 'MyAwesomeCommand', 'module' => 'Blog', '--command' => 'my:awesome']
        );

        $file = $this->finder->get($this->modulePath.'/Console/MyAwesomeCommand.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace()
    {
        $this->app['config']->set('modules.paths.generator.command.path', 'app/CustomCommands');

        $code = $this->artisan('module:make-command', ['name' => 'AwesomeCommand', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/CustomCommands/AwesomeCommand.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace_specific()
    {
        $this->app['config']->set('modules.paths.generator.command.namespace', 'Commands');

        $code = $this->artisan('module:make-command', ['name' => 'AwesomeCommand', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/Console/AwesomeCommand.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_generated_command_reads_the_default_name_and_description_from_attributes()
    {
        $this->assertSame(0, $this->artisan('module:make-command', ['name' => 'DefaultNameCommand', 'module' => 'Blog']));

        $command = $this->loadGeneratedCommand('DefaultNameCommand');

        $this->assertSame('command:name', $command->getName());
        $this->assertSame('Command description', $command->getDescription());
        $this->assertSame([], $command->getDefinition()->getArguments());
    }

    public function test_generated_command_registers_and_runs_with_the_given_signature()
    {
        $this->assertSame(0, $this->artisan('module:make-command', [
            'name' => 'SyncBlogCommand',
            'module' => 'Blog',
            '--command' => 'blog:sync {user} {--force}',
        ]));

        $command = $this->loadGeneratedCommand('SyncBlogCommand');

        $this->assertSame('blog:sync', $command->getName());
        $this->assertSame('Command description', $command->getDescription());
        $this->assertSame(['user'], array_keys($command->getDefinition()->getArguments()));
        $this->assertTrue($command->getDefinition()->hasOption('force'));

        $this->app[Kernel::class]->registerCommand($command);

        $this->assertSame(0, $this->artisan('blog:sync', ['user' => '1', '--force' => true]));
    }

    public function test_generated_command_escapes_quotes_and_backslashes_in_the_signature()
    {
        $this->assertSame(0, $this->artisan('module:make-command', [
            'name' => 'QuoteBlogCommand',
            'module' => 'Blog',
            '--command' => "blog:quote {user : The user's id} {--share=\\\\server\\docs}",
        ]));

        $command = $this->loadGeneratedCommand('QuoteBlogCommand');

        $this->assertSame('blog:quote', $command->getName());
        $this->assertSame("The user's id", $command->getDefinition()->getArgument('user')->getDescription());
        $this->assertSame('\\\\server\\docs', $command->getDefinition()->getOption('share')->getDefault());
    }

    /**
     * Load a command class generated in the Blog module and instantiate it.
     */
    private function loadGeneratedCommand(string $class): Command
    {
        $fqcn = 'Modules\\Blog\\Console\\'.$class;

        if (! class_exists($fqcn, false)) {
            require $this->modulePath.'/Console/'.$class.'.php';
        }

        return $this->app->make($fqcn);
    }
}
