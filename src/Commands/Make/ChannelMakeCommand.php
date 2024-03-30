<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

final class ChannelMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The console command name.
     */
    protected $name = 'module:make-channel';

    /**
     * The console command description.
     */
    protected $description = 'Create a new channel class for the specified module.';

    protected $argumentName = 'name';

    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.channels.namespace', $this->getPathNamespace(config('modules.paths.generator.channels.path', 'app/Broadcasting')));
    }

    /**
     * Get template contents.
     */
    protected function getTemplateContents(): string
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/channel.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS'     => $this->getClass(),
        ]))->render();
    }

    /**
     * Get the destination file path.
     */
    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $channelPath = GenerateConfigReader::read('channels');

        return $path . $channelPath->getPath() . '/' . $this->getFileName() . '.php';
    }

    private function getFileName(): string
    {
        return Str::studly($this->argument('name'));
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the channel class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }
}
