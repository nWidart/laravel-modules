<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class JobMiddlewareMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    protected $argumentName = 'name';

    protected $name = 'module:make-job-middleware';

    protected $description = 'Create a new job middleware class for the specified module.';

    #[\Override]
    public function getDestinationFilePath(): string
    {
        $path = $this->moduleRepository()->getModulePath($this->getModuleName());

        return $path.$this->getJobMiddlewarePath().'/'.$this->getJobMiddlewareName().'.php';
    }

    #[\Override]
    protected function getTemplateContents(): string
    {
        $module = $this->moduleRepository()->findOrFail($this->getModuleName());

        return (new Stub($this->getStubName(), [
            'CLASS_NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getClassNameWithoutNamespace(),
        ]))->render();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the job middleware class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite the file if it already exists'],
        ];
    }

    protected function getJobMiddlewareName(): string
    {
        return Str::studly($this->argument('name'));
    }

    private function getClassNameWithoutNamespace(): string
    {
        return class_basename($this->getJobMiddlewareName());
    }

    /**
     * The namespace follows the path. Without a job-middleware config key
     * (a config published before the key existed), it follows the jobs
     * namespace: <jobs namespace>\Middleware.
     */
    #[\Override]
    public function getDefaultNamespace(): string
    {
        if ($this->hasJobMiddlewareConfig()) {
            return config('modules.paths.generator.job-middleware.namespace')
                ?? $this->strip_app_folder($this->getJobMiddlewarePath());
        }

        $jobsNamespace = config('modules.paths.generator.jobs.namespace')
            ?? $this->strip_app_folder($this->getJobsPath());

        return $jobsNamespace.'\Middleware';
    }

    /**
     * The modules.paths.generator.job-middleware path, or <jobs path>/Middleware
     * when a published config has no job-middleware key.
     */
    private function getJobMiddlewarePath(): string
    {
        if ($this->hasJobMiddlewareConfig()) {
            return (string) GenerateConfigReader::read('job-middleware')->getPath();
        }

        return $this->getJobsPath().'/Middleware';
    }

    private function hasJobMiddlewareConfig(): bool
    {
        return is_string(GenerateConfigReader::read('job-middleware')->getPath());
    }

    private function getJobsPath(): string
    {
        $path = GenerateConfigReader::read('jobs')->getPath();

        return is_string($path) ? $path : $this->app_path('Jobs');
    }

    protected function getStubName(): string
    {
        return '/job-middleware.stub';
    }
}
