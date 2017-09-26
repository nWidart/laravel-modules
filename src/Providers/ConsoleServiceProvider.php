<?php

namespace Nwidart\Modules\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Commands\CommandMakeCommand;
use Nwidart\Modules\Commands\ControllerMakeCommand;
use Nwidart\Modules\Commands\DisableCommand;
use Nwidart\Modules\Commands\DumpCommand;
use Nwidart\Modules\Commands\EnableCommand;
use Nwidart\Modules\Commands\EventMakeCommand;
use Nwidart\Modules\Commands\JobMakeCommand;
use Nwidart\Modules\Commands\ListenerMakeCommand;
use Nwidart\Modules\Commands\MailMakeCommand;
use Nwidart\Modules\Commands\MiddlewareMakeCommand;
use Nwidart\Modules\Commands\NotificationMakeCommand;
use Nwidart\Modules\Commands\ProviderMakeCommand;
use Nwidart\Modules\Commands\GenerateRouteProviderCommand;
use Nwidart\Modules\Commands\InstallCommand;
use Nwidart\Modules\Commands\ListCommand;
use Nwidart\Modules\Commands\MakeCommand;
use Nwidart\Modules\Commands\FactoryMakeCommand;
use Nwidart\Modules\Commands\PolicyMakeCommand;
use Nwidart\Modules\Commands\MakeRequestCommand;
use Nwidart\Modules\Commands\MakeRuleCommand;
use Nwidart\Modules\Commands\MigrateCommand;
use Nwidart\Modules\Commands\MigrateRefreshCommand;
use Nwidart\Modules\Commands\MigrateResetCommand;
use Nwidart\Modules\Commands\MigrateRollbackCommand;
use Nwidart\Modules\Commands\MigrationCommand;
use Nwidart\Modules\Commands\ModelCommand;
use Nwidart\Modules\Commands\PublishCommand;
use Nwidart\Modules\Commands\PublishConfigurationCommand;
use Nwidart\Modules\Commands\PublishMigrationCommand;
use Nwidart\Modules\Commands\PublishTranslationCommand;
use Nwidart\Modules\Commands\SeedCommand;
use Nwidart\Modules\Commands\SeedMakeCommand;
use Nwidart\Modules\Commands\SetupCommand;
use Nwidart\Modules\Commands\UnUseCommand;
use Nwidart\Modules\Commands\UpdateCommand;
use Nwidart\Modules\Commands\UseCommand;

class ConsoleServiceProvider extends ServiceProvider
{

    protected $defer = false;

    /**
     * The available commands
     *
     * @var array
     */
    protected $commands = [
        CommandMakeCommand::class,
        ControllerMakeCommand::class,
        DisableCommand::class,
        DumpCommand::class,
        EnableCommand::class,
        EventMakeCommand::class,
        JobMakeCommand::class,
        ListenerMakeCommand::class,
        MailMakeCommand::class,
        MiddlewareMakeCommand::class,
        NotificationMakeCommand::class,
        ProviderMakeCommand::class,
        GenerateRouteProviderCommand::class,
        InstallCommand::class,
        ListCommand::class,
        MakeCommand::class,
        FactoryMakeCommand::class,
        PolicyMakeCommand::class,
        MakeRequestCommand::class,
        MakeRuleCommand::class,
        MigrateCommand::class,
        MigrateRefreshCommand::class,
        MigrateResetCommand::class,
        MigrateRollbackCommand::class,
        MigrationCommand::class,
        ModelCommand::class,
        PublishCommand::class,
        PublishConfigurationCommand::class,
        PublishMigrationCommand::class,
        PublishTranslationCommand::class,
        SeedCommand::class,
        SeedMakeCommand::class,
        SetupCommand::class,
        UnUseCommand::class,
        UpdateCommand::class,
        UseCommand::class,
    ];


    /**
     * Register the commands.
     */
    public function register()
    {
        $this->commands($this->commands);
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
