<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ProviderMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name of argument name.
     *
     * @var string
     */
    protected $argumentName = 'name';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-provider';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service provider class for the specified module.';

    /**
     * Get default namespace.
     *
     * @return string
     */
    public function getDefaultNamespace()
    {
        return 'Providers';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The service provider name.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['master', null, InputOption::VALUE_NONE, 'Indicates the master service provider', null],
        ];
    }

    /**
     * @return mixed
     */
    protected function getTemplateContents()
    {
        $stub = $this->option('master') ? 'scaffold/provider' : 'provider';

        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/' . $stub . '.stub', [
            'NAMESPACE'         => $this->getClassNamespace($module),
            'CLASS'             => $this->getClass(),
            'LOWER_NAME'        => $module->getLowerName(),
            'MODULE'            => $this->getModuleName(),
            'NAME'              => $this->getFileName(),
            'STUDLY_NAME'       => $module->getStudlyName(),
            'MODULE_NAMESPACE'  => $this->laravel['modules']->config('namespace'),
            'PATH_VIEWS'        => $this->laravel['config']->get('modules.paths.generator.views'),
            'PATH_LANG'         => $this->laravel['config']->get('modules.paths.generator.lang'),
            'PATH_CONFIG'       => $this->laravel['config']->get('modules.paths.generator.config'),
            'MIGRATIONS_PATH'   => $this->laravel['config']->get('modules.paths.generator.migration', 'Database/Migrations'),
            'FACTORIES_PATH'   => $this->laravel['config']->get('modules.paths.generator.factory', 'Database/factories'),
        ]))->render();
    }

    /**
     * @return mixed
     */
    protected function getDestinationFilePath()
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $generatorPath = $this->laravel['modules']->config('paths.generator.provider');

        return $path . $generatorPath . '/' . $this->getFileName() . '.php';
    }

    /**
     * @return string
     */
    private function getFileName()
    {
        return Str::studly($this->argument('name'));
    }
}
