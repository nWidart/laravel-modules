<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class PublishConfigurationCommand extends Command
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
    protected $description = 'Publish a module\'s config files to the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('publishing module config files...');

        if ($module = $this->argument('module')) {
            $this->publishConfiguration($module);

            return 0;
        }

        foreach ($this->laravel['modules']->allEnabled() as $module) {
            $this->publishConfiguration($module->getName());
        }

        return 0;
    }

    /**
     * @param string $module
     * @return string
     */
    private function getServiceProviderForModule($module)
    {
        $namespace = $this->laravel['config']->get('modules.namespace');
        $studlyName = Str::studly($module);
        $provider = $this->laravel['config']->get('modules.paths.generator.provider.path');
        $provider = str_replace('/', '\\', $provider);

        return "$namespace\\$studlyName\\$provider\\{$studlyName}ServiceProvider";
    }

    /**
     * @param string $module
     */
    private function publishConfiguration($module)
    {
        $this->call('vendor:publish', [
            '--provider' => $this->getServiceProviderForModule($module),
            '--force' => $this->option('force'),
            '--tag' => ['config'],
        ]);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['module', InputArgument::OPTIONAL, 'The name of module being used.'],
        ];
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['--force', '-f', InputOption::VALUE_NONE, 'Force the publishing of config files'],
        ];
    }
}
