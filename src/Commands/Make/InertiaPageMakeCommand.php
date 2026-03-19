<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class InertiaPageMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    protected $argumentName = 'name';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-inertia-page';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Inertia page for the specified module.';

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the Inertia page.'],
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
            'PAGE_NAME' => $this->getPageName(),
        ]))->render();
    }

    /**
     * Get the destination file path.
     */
    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());
        $pagesPath = GenerateConfigReader::read('inertia')->getPath() ?? 'resources/js/Pages';
        $subDirectory = $this->getSubDirectory();

        return $path.$pagesPath.'/'.($subDirectory ? $subDirectory.'/' : '').$this->getFileName();
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
     * Get the page name (basename only, no directory).
     */
    private function getPageName(): string
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
     * Get the stub file name based on the options.
     */
    protected function getStubName(): string
    {
        return match ($this->getInertiaFrontend()) {
            'react' => '/inertia/page-react.stub',
            'svelte' => '/inertia/page-svelte.stub',
            default => '/inertia/page-vue.stub',
        };
    }

    /**
     * Get default namespace.
     */
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.inertia.namespace')
            ?? ltrim(config('modules.paths.generator.inertia.path', 'resources/js/Pages'), config('modules.paths.app_folder', ''));
    }
}
