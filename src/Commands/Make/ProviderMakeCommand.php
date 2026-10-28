<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Module;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
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

    #[\Override]
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.provider.namespace')
            ?? $this->strip_app_folder(config('modules.paths.generator.provider.path', 'Providers'));
    }

    /**
     * Get the console command arguments.
     *
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
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
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getOptions()
    {
        return [
            ['master', null, InputOption::VALUE_NONE, 'Indicates the master service provider', null],
        ];
    }

    /**
     * @return mixed
     */
    #[\Override]
    protected function getTemplateContents()
    {
        $stub = $this->option('master') ? 'scaffold/provider' : 'provider';

        /** @var Module $module */
        $module = $this->moduleRepository()->findOrFail($this->getModuleName());

        return (new Stub('/'.$stub.'.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getClass(),
            'LOWER_NAME' => $module->getLowerName(),
            'MODULE' => $this->getModuleName(),
            'NAME' => $this->getFileName(),
            'STUDLY_NAME' => $module->getStudlyName(),
            'MODULE_NAMESPACE' => $this->moduleRepository()->config('namespace'),
            'PATH_CONFIG' => GenerateConfigReader::read('config')->getPath(),
            'FACTORIES_PATH' => GenerateConfigReader::read('factory')->getPath(),
            'PROVIDERS' => $this->getProvidersReplacement(),
        ]))->render();
    }

    /**
     * Render the entries of the master provider's $providers array,
     * listing only the sub-providers that module:make generates.
     */
    private function getProvidersReplacement(): string
    {
        $providers = array_filter([
            'EventServiceProvider' => GenerateConfigReader::read('event-provider')->generatesWith(true),
            'RouteServiceProvider' => GenerateConfigReader::read('route-provider')->generatesWith(true),
        ]);

        if ($providers === []) {
            return '';
        }

        $lines = array_map(fn (string $provider) => "        {$provider}::class,", array_keys($providers));

        return "\n".implode("\n", $lines)."\n    ";
    }

    /**
     * @return mixed
     */
    #[\Override]
    protected function getDestinationFilePath()
    {
        $path = $this->moduleRepository()->getModulePath($this->getModuleName());

        $generatorPath = GenerateConfigReader::read('provider');

        return $path.$generatorPath->getPath().'/'.$this->getFileName().'.php';
    }

    /**
     * @return string
     */
    private function getFileName()
    {
        return Str::studly($this->argument('name'));
    }
}
