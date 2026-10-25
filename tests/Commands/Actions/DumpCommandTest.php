<?php

namespace Nwidart\Modules\Tests\Commands\Actions;

use Illuminate\Contracts\Console\Kernel;
use Laravel\Prompts\Prompt;
use Nwidart\Modules\Commands\Actions\DumpCommand;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Symfony\Component\Console\Input\InputInterface;

class DumpCommandTest extends BaseTestCase
{
    private RepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule('Blog');
        $this->createModule('Shop');
        $this->repository = $this->app[RepositoryInterface::class];
    }

    protected function tearDown(): void
    {
        $this->repository->delete('Blog');
        $this->repository->delete('Shop');
        parent::tearDown();
    }

    public function test_it_runs_composer_in_each_module_and_restores_the_working_directory()
    {
        $cwd = getcwd();
        $command = $this->registerCommand(['Blog' => 0, 'Shop' => 0]);

        $code = $this->artisan('module:dump', ['module' => ['Blog', 'Shop']]);

        $this->assertSame(0, $code);
        $this->assertSame([
            realpath($this->getModuleBasePath('Blog')),
            realpath($this->getModuleBasePath('Shop')),
        ], $command->ranIn);
        $this->assertSame($cwd, getcwd());
    }

    public function test_it_fails_when_composer_fails_and_still_dumps_the_other_modules()
    {
        $cwd = getcwd();
        $command = $this->registerCommand(['Blog' => 1, 'Shop' => 0]);

        $code = $this->artisan('module:dump', ['module' => ['Blog', 'Shop']]);

        $this->assertSame(1, $code);
        $this->assertCount(2, $command->ranIn);
        $this->assertStringContainsString('FAIL', $this->app[Kernel::class]->output());
        $this->assertSame($cwd, getcwd());
    }

    public function test_it_restores_the_working_directory_when_composer_throws()
    {
        $cwd = getcwd();
        $this->registerCommand(['Blog' => new \RuntimeException('Composer crashed.')]);

        try {
            $this->artisan('module:dump', ['module' => ['Blog']]);
            $this->fail('The command should have thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Composer crashed.', $e->getMessage());
        }

        $this->assertSame($cwd, getcwd());
    }

    public function test_it_reports_the_missing_module_when_the_prompt_cannot_be_shown()
    {
        $command = new class extends DumpCommand
        {
            /**
             * Without a TTY, Laravel turns interactive prompts off even when the input is interactive.
             */
            protected function configurePrompts(InputInterface $input)
            {
                parent::configurePrompts($input);

                Prompt::interactive(false);

                // tests turn the fallback on, and fallbackWhen() cannot turn it off again
                (new \ReflectionProperty(Prompt::class, 'shouldFallback'))->setValue(null, false);
            }
        };
        $command->setLaravel($this->app);
        $this->app[Kernel::class]->registerCommand($command);

        $code = $this->artisan('module:dump');

        $this->assertSame(1, $code);
        $this->assertStringContainsString(
            'No module given. Pass a module name or --all.',
            $this->app[Kernel::class]->output()
        );
    }

    /**
     * Register a module:dump command that fakes composer: it returns the given exit code
     * (or throws the given exception) per module, keyed by the module directory name.
     *
     * @param  array<string, int|\Throwable>  $results
     */
    private function registerCommand(array $results): DumpCommand
    {
        $command = new class($results) extends DumpCommand
        {
            /** @var array<int, string> */
            public array $ranIn = [];

            public function __construct(private array $results)
            {
                parent::__construct();
            }

            protected function runComposerDump(): int
            {
                $this->ranIn[] = getcwd();

                $result = $this->results[basename(getcwd())];

                if ($result instanceof \Throwable) {
                    throw $result;
                }

                return $result;
            }
        };

        $command->setLaravel($this->app);
        $this->app[Kernel::class]->registerCommand($command);

        return $command;
    }
}
