<?php

namespace Nwidart\Modules\Traits;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Machine-readable output for artisan commands with a --json option.
 *
 * In JSON mode standard output gets exactly one JSON document on one line. Everything
 * else the command prints (components, line(), tables, called commands) goes to standard
 * error, or is dropped when the output has no error stream (Artisan::call(), tests).
 *
 * The command declares the --json option itself, calls enterJsonMode() first in handle()
 * and writeJson() once with the document.
 */
trait WritesJsonOutput
{
    /**
     * The output that receives the JSON document; null outside JSON mode.
     */
    private ?OutputInterface $jsonOutput = null;

    /**
     * Switch into JSON mode when the command runs with --json. Returns whether JSON mode is on.
     */
    protected function enterJsonMode(): bool
    {
        if ($this->jsonOutput !== null) {
            return true;
        }

        if (! $this->hasOption('json') || ! $this->option('json')) {
            return false;
        }

        $this->jsonOutput = $this->output->getOutput();

        $messages = $this->jsonOutput instanceof ConsoleOutputInterface
            ? $this->jsonOutput->getErrorOutput()
            : new NullOutput;

        $this->output = $this->laravel->make(OutputStyle::class, ['input' => $this->input, 'output' => $messages]);
        $this->components = $this->laravel->make(Factory::class, ['output' => $this->output]);

        Prompt::setOutput($this->output);

        return true;
    }

    /**
     * Whether the command is in JSON mode.
     */
    protected function inJsonMode(): bool
    {
        return $this->jsonOutput !== null;
    }

    /**
     * Write the JSON document to standard output as one line, unformatted.
     *
     * @param  array<mixed>  $document
     */
    protected function writeJson(array $document): void
    {
        $json = json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        ($this->jsonOutput ?? $this->output->getOutput())
            ->writeln($json, OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET);
    }
}
