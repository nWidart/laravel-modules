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
     * The output that receives the JSON document of the run that entered JSON mode.
     */
    private ?OutputInterface $jsonOutput = null;

    /**
     * The output style for messages that enterJsonMode() put in place of $this->output.
     */
    private ?OutputStyle $jsonMessages = null;

    /**
     * Switch into JSON mode when the command runs with --json. Returns whether JSON mode is on.
     */
    protected function enterJsonMode(): bool
    {
        if ($this->inJsonMode()) {
            return true;
        }

        // A command instance can run again (Artisan::call() reuses it): start this run fresh.
        $this->jsonOutput = null;
        $this->jsonMessages = null;

        if (! $this->hasOption('json') || ! $this->option('json')) {
            return false;
        }

        $this->jsonOutput = $this->output->getOutput();

        $messages = $this->jsonOutput instanceof ConsoleOutputInterface
            ? $this->jsonOutput->getErrorOutput()
            : new NullOutput;

        $this->output = $this->jsonMessages = $this->laravel->make(OutputStyle::class, ['input' => $this->input, 'output' => $messages]);
        $this->components = $this->laravel->make(Factory::class, ['output' => $this->output]);

        Prompt::setOutput($this->output);

        return true;
    }

    /**
     * Whether the command is in JSON mode. Command::run() sets a new $this->output for each
     * run, so a run after a --json run is not in JSON mode until it calls enterJsonMode().
     */
    protected function inJsonMode(): bool
    {
        return $this->jsonOutput !== null && $this->output === $this->jsonMessages;
    }

    /**
     * Write the JSON document to standard output as one line, unformatted.
     *
     * @param  array<mixed>  $document
     */
    protected function writeJson(array $document): void
    {
        // Input such as a module name can hold invalid UTF-8: substitute it, so the document is still written and parseable.
        $json = json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);

        ($this->inJsonMode() ? $this->jsonOutput : $this->output->getOutput())
            ->writeln($json, OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET);
    }
}
