<?php

namespace Nwidart\Modules\Traits;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Nwidart\Modules\Support\GeneratorRun;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * --dry-run and --json for generator commands (module:make and every GeneratorCommand).
 *
 * The outermost generator binds a Support\GeneratorRun in the container while it runs.
 * The generators it calls with $this->call() find that run and record into it; only the
 * outermost one prints the result: a list of files, or one JSON document.
 *
 * During a dry run the command's own output (task lines, info lines, the output of the
 * commands it calls) is dropped, because it would describe writes that do not happen.
 * Errors are recorded in the run and printed after the list.
 *
 * A real run with --json prints as without it, to standard error (enterJsonMode()), and
 * then the document with the result of each file on standard output.
 */
trait PlansGeneratorRun
{
    use WritesJsonOutput;

    /**
     * The options addGeneratorRunOptions() added. An option the command declares itself
     * keeps the command's own meaning, and the generator run ignores it.
     *
     * @var array<string, bool>
     */
    private array $generatorRunOptions = [];

    /**
     * Add the --dry-run and --json options, unless the command declares them already.
     */
    protected function addGeneratorRunOptions(): void
    {
        $definition = $this->getDefinition();

        foreach ([
            'dry-run' => 'List the files the command would write, and write nothing',
            'json' => 'Print the result as one JSON document',
        ] as $name => $description) {
            if (! $definition->hasOption($name)) {
                $this->addOption($name, null, InputOption::VALUE_NONE, $description);
                $this->generatorRunOptions[$name] = true;
            }
        }
    }

    /**
     * Run the command in a generator run when --dry-run or --json is given and no run is active yet.
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // A generator called by another generator records into the caller's run.
        if (GeneratorRun::current() !== null) {
            return parent::execute($input, $output);
        }

        $dryRun = $this->generatorRunOption('dry-run');
        $json = $this->generatorRunOption('json');

        if (! $dryRun && ! $json) {
            return parent::execute($input, $output);
        }

        $run = new GeneratorRun((string) $this->getName(), $dryRun);

        if ($json) {
            $this->enterJsonMode();
        }

        $outputStyle = $this->output;
        $components = $this->components;

        // A real run prints as usual (to standard error in JSON mode). A dry run prints
        // nothing while it runs: its task lines would describe writes that do not happen.
        if ($dryRun) {
            $this->output = $this->laravel->make(OutputStyle::class, ['input' => $this->input, 'output' => new NullOutput]);
            $this->components = $this->laravel->make(Factory::class, ['output' => $this->output]);
        }

        $this->laravel->instance(GeneratorRun::class, $run);

        try {
            $code = parent::execute($input, $output);
        } catch (\Throwable $e) {
            // Still print the document for what was done; the exception ends the command as before.
            if ($json) {
                $run->addError($e->getMessage());
                $this->restoreOutput($outputStyle, $components);
                $this->writeJson($run->toArray());
            }

            throw $e;
        } finally {
            $this->laravel->forgetInstance(GeneratorRun::class);
            $this->restoreOutput($outputStyle, $components);
        }

        return $this->reportGeneratorRun($run, $code);
    }

    /**
     * Put back the output a dry run replaced.
     */
    private function restoreOutput(OutputStyle $output, Factory $components): void
    {
        $this->output = $output;
        $this->components = $components;
    }

    /**
     * Print an error, and record it in the active generator run.
     */
    protected function reportError(string $message): void
    {
        $this->components->error($message);

        GeneratorRun::current()?->addError($message);
    }

    /**
     * Whether an option that addGeneratorRunOptions() added is given.
     */
    private function generatorRunOption(string $name): bool
    {
        return ($this->generatorRunOptions[$name] ?? false) && $this->option($name);
    }

    /**
     * Print the run: one JSON document with --json, else one line per file, then the errors.
     */
    private function reportGeneratorRun(GeneratorRun $run, int $code): int
    {
        if ($this->inJsonMode()) {
            $this->writeJson($run->toArray());

            return $code;
        }

        // A real run without --json is not a generator run: it printed as it went.
        if (! $run->isDryRun()) {
            return $code;
        }

        if ($run->files() !== []) {
            $this->components->info('Dry run: these files would be written. Nothing was written.');

            foreach ($run->files() as $file) {
                $this->line(sprintf(
                    '  <fg=%s>%s</>  %s%s',
                    match ($file['action']) {
                        GeneratorRun::CREATE => 'green',
                        GeneratorRun::REPLACE => 'yellow',
                        GeneratorRun::DELETE => 'red',
                        default => 'gray',
                    },
                    $file['action'],
                    $this->relativePath($file['path']),
                    $file['action'] === GeneratorRun::KEEP ? ' (exists)' : '',
                ));
            }

            $this->newLine();
        }

        foreach ($run->errors() as $error) {
            $this->components->error($error);
        }

        return $code;
    }

    /**
     * A path relative to the application base path, as the console components print paths.
     */
    private function relativePath(string $path): string
    {
        $base = GeneratorRun::normalizePath($this->laravel->basePath()).'/';

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }
}
