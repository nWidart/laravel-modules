<?php

namespace Nwidart\Modules\Commands\Make;

use Nwidart\Modules\Generators\ModuleGenerator;
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
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getArguments()
    {
        return [
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    #[\Override]
    protected function getOptions()
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
    #[\Override]
    protected function getTemplateContents()
    {
        $module = $this->moduleRepository()->findOrFail($this->getModuleName());

        return (new Stub('/route-provider.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getFileName(),
            'MODULE_NAMESPACE' => $this->moduleRepository()->config('namespace'),
            'MODULE' => $this->getModuleName(),
            'CONTROLLER_NAMESPACE' => $this->getControllerNameSpace(),
            'WEB_ROUTES_PATH' => $this->getWebRoutesPath(),
            'API_ROUTES_PATH' => $this->getApiRoutesPath(),
            'LOWER_NAME' => $module->getLowerName(),
            'KEBAB_NAME' => $module->getKebabName(),
        ]))->sections([
            'WEB_ROUTES' => $this->hasRoutesStub('routes/web'),
            'API_ROUTES' => $this->hasRoutesStub('routes/api'),
        ])->render();
    }

    /**
     * Whether module:make generates the given routes file.
     *
     * A routes file removed from `modules.stubs.files`, or skipped because
     * `paths.generator.routes.generate` is false, is never created, so the
     * provider must not map it.
     */
    private function hasRoutesStub(string $stub): bool
    {
        return array_key_exists($stub, $this->moduleRepository()->config('stubs.files') ?? [])
            && ModuleGenerator::stubIsGenerated($stub);
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
    #[\Override]
    protected function getDestinationFilePath()
    {
        $path = $this->moduleRepository()->getModulePath($this->getModuleName());

        $generatorPath = GenerateConfigReader::read('provider');

        return $path.$generatorPath->getPath().'/'.$this->getFileName().'.php';
    }

    /**
     * @return mixed
     */
    protected function getWebRoutesPath()
    {
        return '/'.$this->moduleRepository()->config('stubs.files.routes/web', 'Routes/web.php');
    }

    /**
     * @return mixed
     */
    protected function getApiRoutesPath()
    {
        return '/'.$this->moduleRepository()->config('stubs.files.routes/api', 'Routes/api.php');
    }

    #[\Override]
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.provider.namespace')
            ?? $this->strip_app_folder(config('modules.paths.generator.provider.path', 'Providers'));
    }

    private function getControllerNameSpace(): string
    {
        $module = $this->moduleRepository();

        return str_replace('/', '\\', $module->config('paths.generator.controller.namespace') ?: $module->config('paths.generator.controller.path', 'Controller'));
    }
}
