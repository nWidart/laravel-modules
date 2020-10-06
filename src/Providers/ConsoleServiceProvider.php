<?php

namespace Nwidart\Modules\Providers;

use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{

    /**
     * Namespace of the console commands
     *
     * @var string
     */
    protected $consoleNamespace = "Nwidart\\Modules\\Commands";

    /**
     * The available commands
     *
     * @var array
     */
    protected $commands = [
        'CommandMakeCommand',
        'ControllerMakeCommand',
        'DisableCommand',
        'DumpCommand',
        'EnableCommand',
        'EventMakeCommand',
        'JobMakeCommand',
        'ListenerMakeCommand',
        'MailMakeCommand',
        'MiddlewareMakeCommand',
        'NotificationMakeCommand',
        'ProviderMakeCommand',
        'RouteProviderMakeCommand',
        'InstallCommand',
        'ListCommand',
        'ModuleDeleteCommand',
        'ModuleMakeCommand',
        'FactoryMakeCommand',
        'PolicyMakeCommand',
        'RequestMakeCommand',
        'RuleMakeCommand',
        'MigrateCommand',
        'MigrateRefreshCommand',
        'MigrateResetCommand',
        'MigrateRollbackCommand',
        'MigrateStatusCommand',
        'MigrationMakeCommand',
        'ModelMakeCommand',
        'PublishCommand',
        'PublishConfigurationCommand',
        'PublishMigrationCommand',
        'PublishTranslationCommand',
        'SeedCommand',
        'SeedMakeCommand',
        'SetupCommand',
        'UnUseCommand',
        'UpdateCommand',
        'UseCommand',
        'ResourceMakeCommand',
        'TestMakeCommand',
        'LaravelModulesV6Migrator',
    ];

    /**
     * Register the commands.
     */
    public function register()
    {
        $this->commands($this->resolveCommands());
    }

    private function resolveCommands()
    {
        $commands = [];

        foreach (config('modules.commands', $this->commands) as $command) {
            $commands[] = $this->consoleNamespace . "\\" . $command;
        }

        return $commands;
    }

    /**
     * @return array
     */
    public function provides()
    {
        $provides = $this->commands;

        return $provides;
    }
}
