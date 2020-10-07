<?php

namespace Nwidart\Modules\Generators;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Commands\GeneratorCommand;
use Nwidart\Modules\Console\Traits\Definitions;
use Nwidart\Modules\Support\Config\GenerateConfigReader;

abstract class Command extends GeneratorCommand
{
    use Definitions;

    /**
     * The name to be appended to the generated resources.
     *
     * @var null|string
     */
    protected $appendable;

    /**
     * Stub file of the resource
     *
     * @var null|string
     */
    protected $stubFile;

    /**
     * Getter for appendable
     *
     * @return void
     */
    public function appendable()
    {
        return $this->appendable;
    }

    /**
     * Getter for the stub file
     *
     * @return void
     */
    public function stubFile()
    {
        return $this->stubFile;
    }

    /**
     * Replacements for the stub file
     *
     * @return array
     */
    public function replaces()
    {
        return [
            //...
        ];
    }

    /**
     * Get stub file contents
     *
     * @return mixed
     */
    protected function getTemplateContents()
    {
        return (new Stub($this->stubFile(), $this->replaces()))->render();
    }

    /**
     * Get destination file path.
     *
     * @return mixed
     */
    public function getDestinationFilePath()
    {
        return  $this->getModulePath() . "/" .
            GenerateConfigReader::read($this->getGeneratorConfigKey())->getPath() . "/" .
            $this->resolveFilename() . '.' . $this->outputExtension;
    }

    /**
     * Method to apply necessary functionality
     * before console command gets executed
     *
     * @return void
     */
    public function before()
    {
        // ...
    }

    /**
     * Method to apply necessary functionality
     * after console command has executed.
     *
     * @return void
     */
    public function after()
    {
        // ...
    }

    /**
     * Execute the console command
     *
     * @return int
     */
    public function handle(): int
    {
        $this->before();

        if (parent::handle() != E_ERROR) {
            $this->after();
        }
        return 0;
    }

    /**
     * Get and resolve the filename.
     *
     * @return string
     */
    protected function getFileName(): string
    {

        $name = Str::studly($this->argument($this->argumentName));
        if ($this->appendable() && !Str::contains(strtolower($name), strtolower($this->appendable()))) {
            $name .= Str::studly($this->appendable());
        }

        return Str::singular(Str::studly($name));
    }
}
