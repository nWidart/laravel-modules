<?php

namespace Nwidart\Modules\Providers;

use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Parent command namespace.
     *
     * @var string
     */
    protected $namespace = 'Nwidart\\Modules\\Commands\\';

    /**
     * The available command shortname.
     *
     * @var array
     */
    protected $commands = [
        'Make',
        'Command',
        'Controller',
        'Disable',
        'Enable',
        'GenerateFilter',
        'GenerateProvider',
        'GenerateRouteProvider',
        'Install',
        'List',
        'Migrate',
        'MigrateRefresh',
        'MigrateReset',
        'MigrateRollback',
        'Migration',
        'Model',
        'Publish',
        'PublishMigration',
        'PublishTranslation',
        'Seed',
        'SeedMake',
        'Setup',
        'Update',
        'Use',
        'Dump',
        'MakeRequest',
    ];

    /**
     * Register the commands.
     */
    public function register()
    {
        foreach ($this->commands as $command) {
            $this->commands($this->namespace.$command.'Command');
        }
    }

    /**
     * @return array
     */
    public function provides()
    {
        $provides = [];

        foreach ($this->commands as $command) {
            $provides[] = $this->namespace.$command.'Command';
        }

        return $provides;
    }
}
