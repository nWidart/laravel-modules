<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Support\Str;
use Nwidart\Modules\Module;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class EventServiceProviderMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name of 'name' argument.
     */
    protected $argumentName = 'module';

    /**
     * The console command name.
     */
    protected $name = 'module:EventServiceProvider';

    /**
     * The console command description.
     */
    protected $description = 'Create a new EventServiceProvider class for the specified module.';

    protected $provider = 'EventServiceProvider';

    public function getDefaultNamespace(): string
    {
        $module = $this->laravel['modules'];

        return $module->config('paths.generator.provider.namespace') ?: $module->config('paths.generator.provider.path', 'app/Providers');
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::REQUIRED, 'The name of module will be used.'],
        ];
    }

    protected function getTemplateContents(): mixed
    {
        /** @var Module $module */
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        $data = [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getClassName(),
        ];

        return (new Stub('/scaffold/EventServiceProvider.stub', $data))->render();
    }

    /**
     * Get the name.
     */
    public function getClassName(): string
    {
        return Str::studly('EventServiceProvider');
    }

    protected function getDestinationFilePath(): mixed
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $generatorPath = GenerateConfigReader::read('provider');

        return $path.$generatorPath->getPath().'/'.$this->getFileName().'.php';
    }

    private function getFileName(): string
    {
        return Str::studly($this->provider);
    }
}
