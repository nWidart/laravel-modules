<?php

namespace Nwidart\Modules\tests\Commands;

use Nwidart\Modules\Tests\BaseTestCase;

class GenerateCommandCommandTest extends BaseTestCase
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
    public function it_generates_a_new_console_command_class()
    {
        $this->artisan('module:make-command', ['name' => 'MyAwesomeCommand', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath . '/Console/MyAwesomeCommand.php'));
    }

    /** @test */
    public function it_generated_correct_file_with_content()
    {
        $this->artisan('module:make-command', ['name' => 'MyAwesomeCommand', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath . '/Console/MyAwesomeCommand.php');

        $this->assertEquals($this->expectedContent(), $file);
    }

    /** @test */
    public function it_uses_set_command_name_in_class()
    {
        $this->artisan('module:make-command', ['name' => 'MyAwesomeCommand', 'module' => 'Blog', '--command' => 'my:awesome']);

        $file = $this->finder->get($this->modulePath . '/Console/MyAwesomeCommand.php');

        $this->assertTrue(str_contains($file, "protected \$name = 'my:awesome';"));
    }

    private function expectedContent()
    {
        return <<<TEXT
<?php

namespace Modules\Blog\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MyAwesomeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected \$name = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected \$description = 'Command description.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        //
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['example', InputArgument::REQUIRED, 'An example argument.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }
}

TEXT;
    }
}
