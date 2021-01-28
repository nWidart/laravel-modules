<?php

namespace Nwidart\Modules\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Laravel\LaravelDatabaseRepository;
use Nwidart\Modules\Laravel\LaravelFileRepository;

class ContractsServiceProvider extends ServiceProvider
{
    /**
     * Register some binding.
     */
    public function register()
    {
        if (config('modules.database_management.enabled')) {
            $this->app->bind(RepositoryInterface::class, LaravelDatabaseRepository::class);
        } else {
            $this->app->bind(RepositoryInterface::class, LaravelFileRepository::class);
        }
    }
}
