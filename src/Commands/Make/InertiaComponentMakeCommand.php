<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class InertiaComponentMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    protected $argumentName = 'name';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-inertia-component';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Inertia component for the specified module.';

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the Inertia component.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['vue', null, InputOption::VALUE_NONE, 'Create a Vue component (default)'],
            ['react', null, InputOption::VALUE_NONE, 'Create a React component'],
            ['svelte', null, InputOption::VALUE_NONE, 'Create a Svelte component'],
        ];
    }

    /**
     * Get template contents.
     */
    protected function getTemplateContents(): string
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub($this->getStubName(), [
            'STUDLY_NAME' => $module->getStudlyName(),
            'COMPONENT_NAME' => $this->getComponentName(),
        ]))->render();
    }

    /**
     * Get the destination file path.
     */
    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());
        $componentsPath = GenerateConfigReader::read('inertia-components')->getPath() ?? 'resources/js/Components';
        $subDirectory = $this->getSubDirectory();

        return $path.$componentsPath.'/'.($subDirectory ? $subDirectory.'/' : '').$this->getFileName();
    }

    /**
     * Get the file name (basename only, no directory).
     */
    private function getFileName(): string
    {
        $extension = match ($this->getInertiaFrontend()) {
            'react' => '.jsx',
            'svelte' => '.svelte',
            default => '.vue',
        };

        return Str::studly(basename(str_replace('\\', '/', $this->argument('name')))).$extension;
    }

    /**
     * Get the component name (basename only, no directory).
     */
    private function getComponentName(): string
    {
        return Str::studly(basename(str_replace('\\', '/', $this->argument('name'))));
    }

    /**
     * Get the subdirectory portion of the name, if any.
     */
    private function getSubDirectory(): string
    {
        $name = str_replace('\\', '/', $this->argument('name'));
        $dir = dirname($name);

        return $dir === '.' ? '' : $dir;
    }

    /**
     * Get the active Inertia frontend, respecting flags then config default.
     */
    private function getInertiaFrontend(): string
    {
        if ($this->option('react')) {
            return 'react';
        }
        if ($this->option('svelte')) {
            return 'svelte';
        }
        if ($this->option('vue')) {
            return 'vue';
        }

        return config('modules.inertia.frontend', 'vue');
    }

    /**
     * Get the stub file name based on the options.
     */
    protected function getStubName(): string
    {
        return match ($this->getInertiaFrontend()) {
            'react' => '/inertia/component-react.stub',
            'svelte' => '/inertia/component-svelte.stub',
            default => '/inertia/component-vue.stub',
        };
    }

    /**
     * Get default namespace.
     */
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.inertia-components.namespace')
            ?? ltrim(config('modules.paths.generator.inertia-components.path', 'resources/js/Components'), config('modules.paths.app_folder', ''));
    }
}
