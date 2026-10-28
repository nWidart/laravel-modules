<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ConfigMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    protected $argumentName = 'name';

    protected $name = 'module:make-config';

    protected $description = 'Create a new configuration file for the specified module.';

    /**
     * The file keeps the name as given, like make:config: "services/stripe"
     * writes config/services/stripe.php, and a ".php" suffix is not doubled.
     */
    #[\Override]
    public function getDestinationFilePath(): string
    {
        $path = $this->moduleRepository()->getModulePath($this->getModuleName());

        $configPath = GenerateConfigReader::read('config')->getPath();

        return $path.(is_string($configPath) ? $configPath : 'config').'/'.Str::finish($this->argument('name'), '.php');
    }

    #[\Override]
    protected function getTemplateContents(): string
    {
        return Stub::create($this->getStubName())->render();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the config file.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite the file if it already exists'],
        ];
    }

    protected function getStubName(): string
    {
        return '/config.stub';
    }
}
