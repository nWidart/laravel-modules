<?php

namespace Nwidart\Modules\Commands;

use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class RouteProviderMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    protected $argumentName = 'module';

    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'module:route-provider';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Create a new route service provider for the specified module.';

    /**
     * The command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get template contents.
     *
     * @return string
     */
    protected function getTemplateContents()
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/route-provider.stub', [
            'NAMESPACE'         => $this->getClassNamespace($module),
            'CLASS'             => $this->getFileName(),
            'MODULE_NAMESPACE' => $this->laravel['modules']->config('namespace'),
            'MODULE'           => $this->getModuleName(),
            'ROUTES_PATH'   => $this->getRoutesPath(),
        ]))->render();
    }

    /**
     * @return string
     */
    private function getFileName()
    {
        return 'RouteServiceProvider';
    }

    /**
     * Get the destination file path.
     *
     * @return string
     */
    protected function getDestinationFilePath()
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $generatorPath = GenerateConfigReader::read('provider');

        return $path . $generatorPath->getPath() . '/' . $this->getFileName() . '.php';
    }

    /**
     * @return mixed
     */
    protected function getRoutesPath()
    {
        return '/' . $this->laravel['config']->get('stubs.files.routes', 'Http/routes.php');
    }

    public function getDefaultNamespace() : string
    {
        return $this->laravel['modules']->config('paths.generator.provider.path', 'Providers');
    }
}
