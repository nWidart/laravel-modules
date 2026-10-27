<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

final class NotificationMakeCommand extends GeneratorCommand
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
    protected $description = 'Create a new notification class for the specified module.';

    #[\Override]
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.notifications.namespace')
            ?? $this->strip_app_folder(config('modules.paths.generator.notifications.path', 'Notifications'));
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

        return (new Stub('/notification.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getClass(),
        ]))->render();
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

        $notificationPath = GenerateConfigReader::read('notifications');

        return $path.$notificationPath->getPath().'/'.$this->getFileName().'.php';
    }

    /**
     * @return string
     */
    private function getFileName()
    {
        return Str::studly($this->argument('name'));
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
            ['name', InputArgument::REQUIRED, 'The name of the notification class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }
}
