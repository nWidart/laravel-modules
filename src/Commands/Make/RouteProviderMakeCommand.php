<?php

namespace Nwidart\Modules\Commands\Make;

use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class RouteProviderMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    protected string $argumentName = 'module';

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
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when the file already exists.'],
        ];
    }

    /**
     * Get template contents.
     *
     * @return string
     */
    protected function getTemplateContents(): string
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/route-provider.stub', [
            'NAMESPACE'            => $this->getClassNamespace($module),
            'CLASS'                => $this->getFileName(),
            'MODULE_NAMESPACE'     => $this->laravel['modules']->config('namespace'),
            'MODULE'               => $this->getModuleName(),
            'CONTROLLER_NAMESPACE' => $this->getControllerNameSpace(),
            'WEB_ROUTES_PATH'      => $this->getWebRoutesPath(),
            'API_ROUTES_PATH'      => $this->getApiRoutesPath(),
            'LOWER_NAME'           => $module->getLowerName(),
        ]))->render();
    }

    private function getFileName(): string
    {
        return 'RouteServiceProvider';
    }

    /**
     * Get the destination file path.
     *
     * @return string
     */
    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $generatorPath = GenerateConfigReader::read('provider');

        return $path . $generatorPath->getPath() . '/' . $this->getFileName() . '.php';
    }

    protected function getWebRoutesPath(): string
    {
        return '/' . $this->laravel['modules']->config('stubs.files.routes/web', 'Routes/web.php');
    }

    protected function getApiRoutesPath(): string
    {
        return '/' . $this->laravel['modules']->config('stubs.files.routes/api', 'Routes/api.php');
    }

    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.provider.namespace')
            ?? ltrim(config('modules.paths.generator.provider.path', 'Providers'), config('modules.paths.app_folder', ''));
    }

    private function getControllerNameSpace(): string
    {
        $module = $this->laravel['modules'];

        return str_replace('/', '\\', $module->config('paths.generator.controller.namespace') ?: $module->config('paths.generator.controller.path', 'Controller'));
    }
}
