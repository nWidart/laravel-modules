<?php

namespace Nwidart\Modules\Commands\Publish;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Nwidart\Modules\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputOption;

class PublishConfigurationCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:publish-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Publish a module's config files to the application";

    /**
     * @param  string  $name
     */
    #[\Override]
    public function executeAction($name): void
    {
        if ($this->call('vendor:publish', [
            '--provider' => $this->getServiceProviderForModule($name),
            '--force' => $this->option('force'),
            '--tag' => ['config'],
        ]) !== self::SUCCESS) {
            $this->markAsFailed();
        }
    }

    #[\Override]
    public function getInfo(): ?string
    {
        return 'Publishing module config files ...';
    }

    private function getServiceProviderForModule(string $module): string
    {
        $moduleModel = $this->getModuleModel($module);
        $config = $this->laravel->make(ConfigRepository::class);

        $namespace = $config->get('modules.namespace');
        $moduleName = $moduleModel->getName();
        $provider = $config->get('modules.paths.generator.provider.path');
        $provider = str_replace($config->get('modules.paths.app_folder'), '', $provider);
        $provider = str_replace('/', '\\', $provider);

        return "$namespace\\$moduleName\\$provider\\{$moduleName}ServiceProvider";
    }

    #[\Override]
    protected function getOptions(): array
    {
        return [
            ['--force', '-f', InputOption::VALUE_NONE, 'Force the publishing of config files'],
        ];
    }
}
