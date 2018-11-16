<?php

namespace Nwidart\Modules\Commands;

use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

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
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['api', null, InputOption::VALUE_NONE, 'Indicates the api route service provider', null],
        ];
    }

    /**
     * Get template contents.
     *
     * @return string
     */
    protected function getTemplateContents()
    {
        $stubFile = $this->option('api') ? '/api-route-provider.stub' : '/route-provider.stub';

        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub($stubFile, [
            'NAMESPACE'        => $this->getClassNamespace($module),
            'CLASS'            => $this->getFileName(),
            'MODULE_NAMESPACE' => $this->laravel['modules']->config('namespace'),
            'MODULE'           => $this->getModuleName(),
            'WEB_ROUTES_PATH'  => $this->getWebRoutesPath(),
            'API_ROUTES_PATH'  => $this->getApiRoutesPath(),
            'LOWER_NAME'       => $module->getLowerName(),
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
    protected function getWebRoutesPath()
    {
        return '/' . $this->laravel['config']->get('stubs.files.routes', 'Routes/web.php');
    }

    /**
     * @return mixed
     */
    protected function getApiRoutesPath()
    {
        return '/' . $this->laravel['config']->get('stubs.files.routes', 'Routes/api.php');
    }

    public function getDefaultNamespace() : string
    {
        return $this->laravel['modules']->config('paths.generator.provider.path', 'Providers');
    }
}
