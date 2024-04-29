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
            // Actions Commands
            Commands\Actions\CheckLangCommand::class,
            Commands\Actions\DisableCommand::class,
            Commands\Actions\DumpCommand::class,
            Commands\Actions\EnableCommand::class,
            Commands\Actions\InstallCommand::class,
            Commands\Actions\ListCommand::class,
            Commands\Actions\ModelPruneCommand::class,
            Commands\Actions\ModelShowCommand::class,
            Commands\Actions\ModuleDeleteCommand::class,
            Commands\Actions\UnUseCommand::class,
            Commands\Actions\UpdateCommand::class,
            Commands\Actions\UseCommand::class,

            // Database Commands
            Commands\Database\MigrateCommand::class,
            Commands\Database\MigrateRefreshCommand::class,
            Commands\Database\MigrateResetCommand::class,
            Commands\Database\MigrateRollbackCommand::class,
            Commands\Database\MigrateStatusCommand::class,
            Commands\Database\SeedCommand::class,

            // Make Commands
            Commands\Make\ActionMakeCommand::class,
            Commands\Make\CastMakeCommand::class,
            Commands\Make\ChannelMakeCommand::class,
            Commands\Make\CommandMakeCommand::class,
            Commands\Make\ComponentClassMakeCommand::class,
            Commands\Make\ComponentViewMakeCommand::class,
            Commands\Make\ControllerMakeCommand::class,
            Commands\Make\EventMakeCommand::class,
            Commands\Make\EventProviderMakeCommand::class,
            Commands\Make\EnumMakeCommand::class,
            Commands\Make\ExceptionMakeCommand::class,
            Commands\Make\FactoryMakeCommand::class,
            Commands\Make\InterfaceMakeCommand::class,
            Commands\Make\HelperMakeCommand::class,
            Commands\Make\JobMakeCommand::class,
            Commands\Make\ListenerMakeCommand::class,
            Commands\Make\MailMakeCommand::class,
            Commands\Make\MiddlewareMakeCommand::class,
            Commands\Make\MigrationMakeCommand::class,
            Commands\Make\ModelMakeCommand::class,
            Commands\Make\ModuleMakeCommand::class,
            Commands\Make\NotificationMakeCommand::class,
            Commands\Make\ObserverMakeCommand::class,
            Commands\Make\PolicyMakeCommand::class,
            Commands\Make\ProviderMakeCommand::class,
            Commands\Make\RequestMakeCommand::class,
            Commands\Make\ResourceMakeCommand::class,
            Commands\Make\RouteProviderMakeCommand::class,
            Commands\Make\RuleMakeCommand::class,
            Commands\Make\ScopeMakeCommand::class,
            Commands\Make\SeedMakeCommand::class,
            Commands\Make\ServiceMakeCommand::class,
            Commands\Make\TraitMakeCommand::class,
            Commands\Make\TestMakeCommand::class,
            Commands\Make\ViewMakeCommand::class,

            //Publish Commands
            Commands\Publish\PublishCommand::class,
            Commands\Publish\PublishConfigurationCommand::class,
            Commands\Publish\PublishMigrationCommand::class,
            Commands\Publish\PublishTranslationCommand::class,

            // Other Commands
            Commands\ComposerUpdateCommand::class,
            Commands\LaravelModulesV6Migrator::class,
            Commands\SetupCommand::class,

            Commands\Database\MigrateFreshCommand::class,
        ]);
    }
}
