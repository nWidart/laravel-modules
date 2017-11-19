<?php

namespace Nwidart\Modules\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Commands\CommandMakeCommand;
use Nwidart\Modules\Commands\ControllerMakeCommand;
use Nwidart\Modules\Commands\DisableCommand;
use Nwidart\Modules\Commands\DumpCommand;
use Nwidart\Modules\Commands\EnableCommand;
use Nwidart\Modules\Commands\EventMakeCommand;
use Nwidart\Modules\Commands\FactoryMakeCommand;
use Nwidart\Modules\Commands\InstallCommand;
use Nwidart\Modules\Commands\JobMakeCommand;
use Nwidart\Modules\Commands\ListCommand;
use Nwidart\Modules\Commands\ListenerMakeCommand;
use Nwidart\Modules\Commands\MailMakeCommand;
use Nwidart\Modules\Commands\MiddlewareMakeCommand;
use Nwidart\Modules\Commands\MigrateCommand;
use Nwidart\Modules\Commands\MigrateRefreshCommand;
use Nwidart\Modules\Commands\MigrateResetCommand;
use Nwidart\Modules\Commands\MigrateRollbackCommand;
use Nwidart\Modules\Commands\MigrateStatusCommand;
use Nwidart\Modules\Commands\MigrationMakeCommand;
use Nwidart\Modules\Commands\ModelMakeCommand;
use Nwidart\Modules\Commands\ModuleMakeCommand;
use Nwidart\Modules\Commands\NotificationMakeCommand;
use Nwidart\Modules\Commands\PolicyMakeCommand;
use Nwidart\Modules\Commands\ProviderMakeCommand;
use Nwidart\Modules\Commands\PublishCommand;
use Nwidart\Modules\Commands\PublishConfigurationCommand;
use Nwidart\Modules\Commands\PublishMigrationCommand;
use Nwidart\Modules\Commands\PublishTranslationCommand;
use Nwidart\Modules\Commands\RequestMakeCommand;
use Nwidart\Modules\Commands\ResourceMakeCommand;
use Nwidart\Modules\Commands\RouteProviderMakeCommand;
use Nwidart\Modules\Commands\RuleMakeCommand;
use Nwidart\Modules\Commands\SeedCommand;
use Nwidart\Modules\Commands\SeedMakeCommand;
use Nwidart\Modules\Commands\SetupCommand;
use Nwidart\Modules\Commands\TestMakeCommand;
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
        RouteProviderMakeCommand::class,
        InstallCommand::class,
        ListCommand::class,
        ModuleMakeCommand::class,
        FactoryMakeCommand::class,
        PolicyMakeCommand::class,
        RequestMakeCommand::class,
        RuleMakeCommand::class,
        MigrateCommand::class,
        MigrateRefreshCommand::class,
        MigrateResetCommand::class,
        MigrateRollbackCommand::class,
        MigrateStatusCommand::class,
        MigrationMakeCommand::class,
        ModelMakeCommand::class,
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
        ResourceMakeCommand::class,
        TestMakeCommand::class,
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
