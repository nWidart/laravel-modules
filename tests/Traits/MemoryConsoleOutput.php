<?php

namespace Nwidart\Modules\Tests\Traits;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

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
