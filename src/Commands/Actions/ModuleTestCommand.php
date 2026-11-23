<?php

namespace Nwidart\Modules\Commands\Actions;

use Dotenv\Exception\InvalidPathException;
use Dotenv\Parser\Parser;
use Dotenv\Store\StoreBuilder;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Traits\WritesJsonOutput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleTestCommand extends BaseCommand
{
    use WritesJsonOutput;

    public const RUNNER_ARTISAN = 'artisan';

    public const RUNNER_PEST = 'pest';

    public const RUNNER_PHPUNIT = 'phpunit';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the tests of the specified modules.';

    /**
     * The test runner of this run: one of the RUNNER_* constants.
     */
    private string $runner = self::RUNNER_PHPUNIT;

    /**
     * The result of each module, in the shape of the JSON document.
     *
     * @var list<array{name: string, ran: bool, passed: ?int, failed: ?int, skipped: ?int, exit_code: ?int}>
     */
    private array $results = [];

    /**
     * Execute the console command.
     */
    #[\Override]
    public function handle(): int
    {
        $this->enterJsonMode();

        $this->results = [];
        $this->runner = $this->resolveRunner();

        if ($this->option('parallel') && $this->runner === self::RUNNER_PHPUNIT) {
            $this->components->error('--parallel needs artisan test or Pest.');

            return $this->finish(self::FAILURE);
        }

        $code = parent::handle();

        return $this->finish($this->firstFailingExitCode() ?? $code);
    }

    /**
     * @param  string  $name
     */
    #[\Override]
    public function executeAction($name): int
    {
        $module = $this->getModuleModel($name);
        $folder = $module->getPath().DIRECTORY_SEPARATOR.'tests';

        if (! is_dir($folder)) {
            $this->components->warn("Module [{$module->getName()}] has no tests folder.");

            $this->results[] = [
                'name' => $module->getName(),
                'ran' => false,
                'passed' => null,
                'failed' => null,
                'skipped' => null,
                'exit_code' => null,
            ];

            return self::SUCCESS;
        }

        $junit = sys_get_temp_dir().DIRECTORY_SEPARATOR.'module-test-'.Str::random(20).'.xml';

        try {
            $result = Process::path(base_path())
                ->forever()
                ->env($this->clearedEnvironment())
                ->start($this->buildCommand($folder, $junit))
                ->wait(function (string $type, string $buffer) {
                    $this->output->write($buffer, false, OutputInterface::OUTPUT_RAW);
                });

            $counts = $this->readJunit($junit);
        } finally {
            if (is_file($junit)) {
                @unlink($junit);
            }
        }

        $exitCode = (int) $result->exitCode();

        $this->results[] = [
            'name' => $module->getName(),
            'ran' => true,
            ...$counts,
            'exit_code' => $exitCode,
        ];

        return $exitCode;
    }

    /**
     * Get the test runner: artisan test when the app has it, else Pest when installed, else PHPUnit.
     */
    protected function resolveRunner(): string
    {
        if ($this->getApplication()?->has('test')) {
            return self::RUNNER_ARTISAN;
        }

        if (is_file(base_path('vendor/bin/pest'))) {
            return self::RUNNER_PEST;
        }

        return self::RUNNER_PHPUNIT;
    }

    /**
     * Build the command line that runs the tests in the given folder.
     *
     * The options come before the folder: artisan test (Collision) stops reading its own
     * options, such as --parallel, after the first argument and passes the rest to PHPUnit.
     *
     * @return list<string>
     */
    protected function buildCommand(string $folder, string $junit): array
    {
        $command = match ($this->runner) {
            self::RUNNER_ARTISAN => [PHP_BINARY, 'artisan', 'test'],
            self::RUNNER_PEST => [base_path('vendor/bin/pest')],
            default => [base_path('vendor/bin/phpunit')],
        };

        if ($this->option('parallel')) {
            $command[] = '--parallel';
        }

        if (is_string($filter = $this->option('filter')) && $filter !== '') {
            $command[] = '--filter='.$filter;
        }

        $command[] = '--log-junit';
        $command[] = $junit;
        $command[] = $folder;

        return $command;
    }

    /**
     * Unset the variables the app loaded from its .env file, as artisan test does, so the
     * runner gets the values of phpunit.xml instead of the app's local ones. With --env the
     * environment is kept.
     *
     * @return array<string, false>
     */
    protected function clearedEnvironment(): array
    {
        if ($this->option('env')) {
            return [];
        }

        try {
            $content = StoreBuilder::createWithNoNames()
                ->addPath($this->laravel->environmentPath())
                ->addName($this->laravel->environmentFile())
                ->make()
                ->read();
        } catch (InvalidPathException) {
            return [];
        }

        $variables = [];

        foreach ((new Parser)->parse($content) as $entry) {
            $variables[$entry->getName()] = false;
        }

        return $variables;
    }

    /**
     * Read the test counts from a JUnit report. The counts are null when the report is
     * missing or cannot be parsed, for example when the runner crashed.
     *
     * @return array{passed: ?int, failed: ?int, skipped: ?int}
     */
    protected function readJunit(string $path): array
    {
        $none = ['passed' => null, 'failed' => null, 'skipped' => null];

        if (! is_file($path) || ($xml = (string) file_get_contents($path)) === '') {
            return $none;
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $root = simplexml_load_string($xml);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($root === false) {
            return $none;
        }

        $suites = isset($root['tests']) || $root->getName() === 'testsuite' ? [$root] : $root->testsuite;

        $totals = ['tests' => 0, 'failures' => 0, 'errors' => 0, 'skipped' => 0];

        foreach ($suites as $suite) {
            foreach (array_keys($totals) as $attribute) {
                $totals[$attribute] += (int) $suite[$attribute];
            }
        }

        $failed = $totals['failures'] + $totals['errors'];

        return [
            'passed' => max(0, $totals['tests'] - $failed - $totals['skipped']),
            'failed' => $failed,
            'skipped' => $totals['skipped'],
        ];
    }

    /**
     * Get the first non-zero exit code of the modules that ran, or null when none failed.
     */
    private function firstFailingExitCode(): ?int
    {
        foreach ($this->results as $result) {
            if (! empty($result['exit_code'])) {
                return $result['exit_code'];
            }
        }

        return null;
    }

    /**
     * Write the JSON document in JSON mode and return the exit code.
     */
    private function finish(int $exitCode): int
    {
        if ($this->inJsonMode()) {
            $totals = ['passed' => 0, 'failed' => 0, 'skipped' => 0];

            foreach ($this->results as $result) {
                foreach (array_keys($totals) as $key) {
                    $totals[$key] += $result[$key] ?? 0;
                }
            }

            $this->writeJson([
                'modules' => $this->results,
                ...$totals,
                'exit_code' => $exitCode,
            ]);
        }

        return $exitCode;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getOptions(): array
    {
        return [
            ['filter', null, InputOption::VALUE_REQUIRED, 'Run only the tests that match the given pattern'],
            ['parallel', null, InputOption::VALUE_NONE, 'Run the tests in parallel (artisan test or Pest)'],
            ['json', null, InputOption::VALUE_NONE, 'Print the result as one JSON document'],
        ];
    }
}
