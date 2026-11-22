<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Module;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class HealthIndicatorMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    protected $argumentName = 'name';

    protected $name = 'module:make-health-indicator';

    protected $description = 'Create a new nwidart/laravel-actuator health indicator for the specified module.';

    #[\Override]
    public function handle(): int
    {
        if (parent::handle() === E_ERROR) {
            return E_ERROR;
        }

        $module = $this->module();
        $key = $this->getIndicatorKey();

        $this->components->info("Register it as [module:{$module->getLowerName()}:{$key}] in the \$healthIndicators property of the module service provider:");
        $this->line("  '{$key}' => \\{$this->getClassNamespace($module)}\\{$this->getClassNameWithoutNamespace()}::class,");
        $this->newLine();

        return 0;
    }

    #[\Override]
    public function getDestinationFilePath(): string
    {
        $path = $this->moduleRepository()->getModulePath($this->getModuleName());

        $filePath = GenerateConfigReader::read('health')->getPath() ?? config('modules.paths.app_folder').'Health';

        return $path.$filePath.'/'.$this->getIndicatorName().'.php';
    }

    #[\Override]
    protected function getTemplateContents(): string
    {
        return (new Stub('/health-indicator.stub', [
            'CLASS_NAMESPACE' => $this->getClassNamespace($this->module()),
            'CLASS' => $this->getClassNameWithoutNamespace(),
        ]))->render();
    }

    #[\Override]
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the health indicator class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    #[\Override]
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.health.namespace')
            ?? $this->strip_app_folder(config('modules.paths.generator.health.path', 'Health'));
    }

    /**
     * The class name with its sub folders, each segment in StudlyCase ("api/v2/payments"
     * becomes "Api/V2/Payments"), so the file path and the namespace agree.
     */
    protected function getIndicatorName(): string
    {
        return $this->studly_path(str_replace('\\', '/', $this->argument('name')));
    }

    /**
     * The namespace of the generated class, built from the same segments as the file path.
     *
     * @param  Module  $module
     */
    #[\Override]
    public function getClassNamespace($module): string
    {
        $name = $this->getIndicatorName();
        $folder = str_contains($name, '/') ? Str::beforeLast($name, '/') : '';

        return $this->module_namespace(
            $module->getStudlyName(),
            $this->getDefaultNamespace().($folder !== '' ? '\\'.$this->path_namespace($folder) : '')
        );
    }

    private function getClassNameWithoutNamespace(): string
    {
        return class_basename($this->getIndicatorName());
    }

    /**
     * The suggested indicator key: the class name without a "HealthIndicator" suffix, in camel case
     * like the actuator's own indicators (PaymentsHealthIndicator => payments).
     */
    private function getIndicatorKey(): string
    {
        $class = $this->getClassNameWithoutNamespace();

        return Str::camel(Str::replaceEnd('HealthIndicator', '', $class) ?: $class);
    }
}
