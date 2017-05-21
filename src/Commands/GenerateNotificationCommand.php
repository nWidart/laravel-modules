<?php

namespace Nwidart\Modules\Commands;

use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

final class GenerateNotificationCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-notification';

    protected $argumentName = 'name';

     /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new notification class for the specified module.';

    /**
     * Get template contents.
     *
     * @return string
     */
    protected function getTemplateContents()
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/notification.stub', [
            'NAMESPACE'         => $this->getClassNamespace($module),
            'CLASS'             => $this->getClass(),
        ]))->render();
    }

    /**
     * Get the destination file path.
     *
     * @return string
     */
    protected function getDestinationFilePath()
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $mailPath = $this->laravel['modules']->config('paths.generator.notifications', 'Notifications');

        return $path . $mailPath . '/' . $this->getFileName() . '.php';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the notification class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * @return string
     */
    private function getFileName()
    {
        return studly_case($this->argument('name'));
    }

    /**
     * @return string
     */
    public function getDefaultNamespace()
    {
        return $this->laravel['modules']->config('paths.generator.notifications', 'Notifications');
    }
}
