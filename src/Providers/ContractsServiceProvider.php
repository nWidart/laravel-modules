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
            $repository = LaravelDatabaseRepository::class;
            $customRepository = config('modules.database_management.repository');
            if ($customRepository && class_exists($customRepository)) {
                $repository = $customRepository;
            }
            $this->app->bind(RepositoryInterface::class, $repository);
        } else {
            $this->app->bind(RepositoryInterface::class, LaravelFileRepository::class);
        }
    }
}
