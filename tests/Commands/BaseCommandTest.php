<?php

namespace Nwidart\Modules\Tests\Commands;

use Illuminate\Contracts\Console\Kernel;
use Laravel\Prompts\Prompt;
use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Tests\BaseTestCase;
use Symfony\Component\Console\Input\InputInterface;

class BaseCommandTest extends BaseTestCase
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

    public function test_it_succeeds_when_every_module_action_succeeds()
    {
        $command = $this->registerCommand(['Blog' => null, 'Shop' => null]);

        $code = $this->artisan('module:base-command-test', ['module' => ['Blog', 'Shop']]);

        $this->assertSame(0, $code);
        $this->assertSame(['Blog', 'Shop'], $command->ran);
    }

    public function test_it_fails_when_a_module_action_returns_false_and_runs_the_remaining_modules()
    {
        $command = $this->registerCommand(['Blog' => false, 'Shop' => null]);

        $code = $this->artisan('module:base-command-test', ['module' => ['Blog', 'Shop']]);

        $this->assertSame(1, $code);
        $this->assertSame(['Blog', 'Shop'], $command->ran);
    }

    public function test_it_fails_when_a_module_action_returns_a_failure_code()
    {
        $command = $this->registerCommand(['Blog' => null, 'Shop' => BaseCommand::FAILURE]);

        $code = $this->artisan('module:base-command-test', ['module' => ['Blog', 'Shop']]);

        $this->assertSame(1, $code);
        $this->assertSame(['Blog', 'Shop'], $command->ran);
    }

    public function test_it_fails_when_a_module_action_marks_the_run_as_failed()
    {
        $command = $this->registerCommand(['Blog' => 'mark', 'Shop' => null]);

        $code = $this->artisan('module:base-command-test', ['module' => ['Blog', 'Shop']]);

        $this->assertSame(1, $code);
        $this->assertSame(['Blog', 'Shop'], $command->ran);
    }

    public function test_a_failure_does_not_carry_over_to_the_next_run()
    {
        $command = $this->registerCommand(['Blog' => false, 'Shop' => null]);

        $this->assertSame(1, $this->artisan('module:base-command-test', ['module' => ['Blog']]));
        $this->assertSame(0, $this->artisan('module:base-command-test', ['module' => ['Shop']]));
        $this->assertSame(['Blog', 'Shop'], $command->ran);
    }

    public function test_it_fails_without_a_module_when_not_interactive()
    {
        $command = $this->registerCommand([]);

        $code = $this->artisan('module:base-command-test', ['--no-interaction' => true]);

        $this->assertSame(1, $code);
        $this->assertSame([], $command->ran);
        $this->assertStringContainsString(
            'No module given. Pass a module name or --all.',
            $this->app[Kernel::class]->output()
        );
    }

    public function test_it_fails_without_a_module_when_prompts_cannot_ask()
    {
        $command = $this->registerCommand([], promptsCanAsk: false);

        $code = $this->artisan('module:base-command-test');

        $this->assertSame(1, $code);
        $this->assertSame([], $command->ran);
        $this->assertStringContainsString(
            'No module given. Pass a module name or --all.',
            $this->app[Kernel::class]->output()
        );
    }

    public function test_it_runs_every_module_with_all_without_interaction()
    {
        $command = $this->registerCommand([]);

        $code = $this->artisan('module:base-command-test', ['--all' => true, '--no-interaction' => true]);

        $this->assertSame(0, $code);
        // --all passes the repository keys, which are lower case
        $this->assertEqualsCanonicalizing(['blog', 'shop'], array_map('strtolower', $command->ran));
    }

    public function test_it_succeeds_with_all_when_there_are_no_modules()
    {
        $this->repository->delete('Blog');
        $this->repository->delete('Shop');
        $command = $this->registerCommand([]);

        try {
            $code = $this->artisan('module:base-command-test', ['--all' => true, '--no-interaction' => true]);
        } finally {
            $this->createModule('Blog');
            $this->createModule('Shop');
        }

        $this->assertSame(0, $code);
        $this->assertSame([], $command->ran);
    }

    public function test_it_checks_every_module_exists_before_running_any_action()
    {
        $command = $this->registerCommand([]);

        try {
            $this->artisan('module:base-command-test', ['module' => ['Blog', 'Typo', 'Shop']]);
            $this->fail('An unknown module should have thrown.');
        } catch (ModuleNotFoundException $e) {
            $this->assertStringContainsString('Typo', $e->getMessage());
        }

        $this->assertSame([], $command->ran);
    }

    /**
     * Register a command whose action returns the given result per module:
     * a value to return, or 'mark' to call markAsFailed().
     *
     * @param  array<string, mixed>  $results
     * @param  bool  $promptsCanAsk  false to behave like a shell without a TTY
     */
    private function registerCommand(array $results, bool $promptsCanAsk = true): BaseCommand
    {
        $command = new class($results, $promptsCanAsk) extends BaseCommand
        {
            protected $name = 'module:base-command-test';

            /** @var array<int, string> */
            public array $ran = [];

            public function __construct(private array $results, private bool $promptsCanAsk)
            {
                parent::__construct();
            }

            /**
             * Without a TTY, Laravel turns interactive prompts off even when the input is interactive.
             */
            protected function configurePrompts(InputInterface $input)
            {
                parent::configurePrompts($input);

                if (! $this->promptsCanAsk) {
                    Prompt::interactive(false);

                    // tests turn the fallback on, and fallbackWhen() cannot turn it off again
                    (new \ReflectionProperty(Prompt::class, 'shouldFallback'))->setValue(null, false);
                }
            }

            public function executeAction($name)
            {
                $this->ran[] = $name;

                $result = $this->results[$name] ?? null;

                if ($result === 'mark') {
                    $this->markAsFailed();

                    return null;
                }

                return $result;
            }
        };

        $command->setLaravel($this->app);
        $this->app[Kernel::class]->registerCommand($command);

        return $command;
    }
}
