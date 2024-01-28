<?php

namespace Nwidart\Modules\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Commands;

class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands(config('modules.commands', self::defaultCommands()->toArray()));
    }

    public function provides(): array
    {
        return self::defaultCommands()->toArray();
    }

    /**
     * Get the package default commands.
     *
     * @return Collection
     */
    public static function defaultCommands(): Collection
    {
        return collect([
            Commands\ChannelMakeCommand::class,
            Commands\CheckLangCommand::class,
            Commands\CommandMakeCommand::class,
            Commands\ComponentClassMakeCommand::class,
            Commands\ComponentViewMakeCommand::class,
            Commands\ControllerMakeCommand::class,
            Commands\DisableCommand::class,
            Commands\DumpCommand::class,
            Commands\EnableCommand::class,
            Commands\EventMakeCommand::class,
            Commands\FactoryMakeCommand::class,
            Commands\InstallCommand::class,
            Commands\JobMakeCommand::class,
            Commands\LaravelModulesV6Migrator::class,
            Commands\ListCommand::class,
            Commands\ListenerMakeCommand::class,
            Commands\MailMakeCommand::class,
            Commands\MiddlewareMakeCommand::class,
            Commands\MigrateCommand::class,
            Commands\MigrateFreshCommand::class,
            Commands\MigrateRefreshCommand::class,
            Commands\MigrateResetCommand::class,
            Commands\MigrateRollbackCommand::class,
            Commands\MigrateStatusCommand::class,
            Commands\MigrationMakeCommand::class,
            Commands\ModelMakeCommand::class,
            Commands\ModelPruneCommand::class,
            Commands\ModelShowCommand::class,
            Commands\ModuleDeleteCommand::class,
            Commands\ModuleMakeCommand::class,
            Commands\NotificationMakeCommand::class,
            Commands\ObserverMakeCommand::class,
            Commands\PolicyMakeCommand::class,
            Commands\ProviderMakeCommand::class,
            Commands\PublishCommand::class,
            Commands\PublishConfigurationCommand::class,
            Commands\PublishMigrationCommand::class,
            Commands\PublishTranslationCommand::class,
            Commands\RequestMakeCommand::class,
            Commands\ResourceMakeCommand::class,
            Commands\RouteProviderMakeCommand::class,
            Commands\RuleMakeCommand::class,
            Commands\SeedCommand::class,
            Commands\SeedMakeCommand::class,
            Commands\SetupCommand::class,
            Commands\TestMakeCommand::class,
            Commands\UnUseCommand::class,
            Commands\UpdateCommand::class,
            Commands\UseCommand::class,
        ]);
    }
}
