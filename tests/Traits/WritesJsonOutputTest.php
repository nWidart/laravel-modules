<?php

namespace Nwidart\Modules\Tests\Traits;

use Illuminate\Console\Command;
use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Commands\Make\GeneratorCommand;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;
use Nwidart\Modules\Traits\WritesJsonOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

class WritesJsonOutputTest extends BaseTestCase
{
    private string $generatedFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generatedFile = sys_get_temp_dir().'/writes-json-output-'.uniqid().'.txt';
    }

    protected function tearDown(): void
    {
        @unlink($this->generatedFile);

        $repository = $this->app[RepositoryInterface::class];

        if ($repository->has('Blog')) {
            $repository->delete('Blog');
        }

        parent::tearDown();
    }

    public function test_json_goes_to_stdout_and_messages_to_stderr()
    {
        $output = new MemoryConsoleOutput;

        $code = $this->runCommand($this->plainCommand(), ['--json' => true], $output);

        $this->assertSame(0, $code);
        $this->assertSame('{"status":"done","note":"<info>literal</info>","path":"a/b","name":"Zoë"}'."\n", $output->stdout());
        $this->assertStringContainsString('Working on it', $output->stderr());
        $this->assertStringContainsString('a plain line', $output->stderr());
    }

    public function test_decorated_output_gets_no_ansi_codes_in_the_json()
    {
        $output = new MemoryConsoleOutput(decorated: true);

        $this->runCommand($this->plainCommand(), ['--json' => true], $output);

        $this->assertStringNotContainsString("\e[", $output->stdout());
        $this->assertStringContainsString('<info>literal</info>', $output->stdout());
        $this->assertStringContainsString("\e[", $output->stderr());
    }

    public function test_json_is_written_in_quiet_mode()
    {
        $output = new MemoryConsoleOutput;
        $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);

        $this->runCommand($this->plainCommand(), ['--json' => true], $output);

        $this->assertStringStartsWith('{"status":"done"', $output->stdout());
    }

    public function test_messages_are_dropped_when_the_output_has_no_error_stream()
    {
        $output = new BufferedOutput(decorated: true);

        $this->runCommand($this->plainCommand(), ['--json' => true], $output);

        $this->assertSame('{"status":"done","note":"<info>literal</info>","path":"a/b","name":"Zoë"}'."\n", $output->fetch());
    }

    public function test_without_json_the_command_prints_as_before()
    {
        $output = new MemoryConsoleOutput;

        $this->runCommand($this->plainCommand(), [], $output);

        $this->assertStringContainsString('Working on it', $output->stdout());
        $this->assertStringContainsString('a plain line', $output->stdout());
        $this->assertStringNotContainsString('{"status"', $output->stdout());
        $this->assertSame('', $output->stderr());
    }

    public function test_it_works_for_a_base_command()
    {
        $this->createModule('Blog');

        $output = new MemoryConsoleOutput;

        $code = $this->runCommand($this->baseCommand(), ['module' => ['Blog'], '--json' => true], $output);

        $this->assertSame(0, $code);
        $this->assertSame('{"modules":["Blog"]}'."\n", $output->stdout());
        $this->assertStringContainsString('Running for all modules', $output->stderr());
        $this->assertStringContainsString('Handled Blog', $output->stderr());
    }

    public function test_it_works_for_a_generator_command()
    {
        $output = new MemoryConsoleOutput;

        $code = $this->runCommand($this->generatorCommand(), ['--json' => true], $output);

        $this->assertSame(0, $code);
        $this->assertFileExists($this->generatedFile);
        $this->assertSame(json_encode(['path' => $this->generatedFile, 'exit_code' => 0], JSON_UNESCAPED_SLASHES)."\n", $output->stdout());
        $this->assertStringContainsString('Generating file', $output->stderr());
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function runCommand(Command $command, array $parameters, OutputInterface $output): int
    {
        $command->setLaravel($this->app);

        $input = new ArrayInput($parameters);
        $input->setInteractive(false);

        return $command->run($input, $output);
    }

    private function plainCommand(): Command
    {
        return new class extends Command
        {
            use WritesJsonOutput;

            protected $signature = 'json-test:plain {--json}';

            public function handle(): int
            {
                $json = $this->enterJsonMode();

                $this->components->info('Working on it');
                $this->line('a plain line');
                $this->newLine();

                if ($json) {
                    $this->writeJson(['status' => 'done', 'note' => '<info>literal</info>', 'path' => 'a/b', 'name' => 'Zoë']);
                }

                return self::SUCCESS;
            }
        };
    }

    private function baseCommand(): BaseCommand
    {
        return new class extends BaseCommand
        {
            use WritesJsonOutput;

            protected $signature = 'json-test:base {--json}';

            /** @var list<string> */
            private array $handled = [];

            public function handle()
            {
                $this->enterJsonMode();

                $code = parent::handle();

                $this->writeJson(['modules' => $this->handled]);

                return $code;
            }

            public function getInfo(): ?string
            {
                return 'Running for all modules';
            }

            public function executeAction($name): void
            {
                $this->handled[] = $name;
                $this->components->info("Handled {$name}");
            }
        };
    }

    private function generatorCommand(): GeneratorCommand
    {
        $command = new class extends GeneratorCommand
        {
            use WritesJsonOutput;

            protected $signature = 'json-test:generator {--json}';

            public string $path = '';

            public function handle(): int
            {
                $this->enterJsonMode();

                $code = parent::handle();

                $this->writeJson(['path' => $this->path, 'exit_code' => $code]);

                return $code;
            }

            protected function getTemplateContents()
            {
                return 'generated';
            }

            protected function getDestinationFilePath()
            {
                return $this->path;
            }
        };

        $command->path = $this->generatedFile;

        return $command;
    }
}

/**
 * A console output with separate in-memory streams for standard output and standard error.
 */
class MemoryConsoleOutput extends StreamOutput implements ConsoleOutputInterface
{
    private OutputInterface $errorOutput;

    public function __construct(bool $decorated = false)
    {
        parent::__construct(fopen('php://memory', 'w+'), self::VERBOSITY_NORMAL, $decorated);

        $this->errorOutput = new StreamOutput(fopen('php://memory', 'w+'), self::VERBOSITY_NORMAL, $decorated);
    }

    public function getErrorOutput(): OutputInterface
    {
        return $this->errorOutput;
    }

    public function setErrorOutput(OutputInterface $error): void
    {
        $this->errorOutput = $error;
    }

    public function section(): ConsoleSectionOutput
    {
        throw new \LogicException('Sections are not supported.');
    }

    public function stdout(): string
    {
        return $this->contents($this);
    }

    public function stderr(): string
    {
        return $this->errorOutput instanceof StreamOutput ? $this->contents($this->errorOutput) : '';
    }

    private function contents(StreamOutput $output): string
    {
        rewind($output->getStream());

        return (string) stream_get_contents($output->getStream());
    }
}
