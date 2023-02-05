<?php

namespace Nwidart\Modules\Providers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Contracts\RepositoryInterface;

class BootstrapServiceProvider extends ServiceProvider
{
    /**
     * Booting the package.
     */
    public function boot(): void
    {
        if ($this->isExcludeBoot() === false) {
            $this->app[RepositoryInterface::class]->boot();
        }

        if ($this->app->runningInConsole()) {
            $this->registerMigrations();

            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'module-migrations');
        }
    }

    /**
     * Register the provider.
     */
    public function register(): void
    {
        $this->app[RepositoryInterface::class]->register();
    }

    private function registerMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    /**
     * Should boot all modules or not in specific commands.
     *
     * @return bool
     */
    private function isExcludeBoot(): bool
    {
        $excludeCommands = config('modules.exclude_boot_commands');
        $command = Request::server('argv');
        if (is_array($command) && isset($command[1])) {
            $commandName = $command[1];
            foreach ($excludeCommands as $className) {
                $commandClass = resolve($className);
                if (!$commandClass instanceof Command) {
                    continue;
                }
                if ($commandName === $commandClass->getName()) {
                    return true;
                }
            }
        }
        return false;
    }
}
