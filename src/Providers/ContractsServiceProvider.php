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
        switch ($this->app['config']->get('modules.activator')) {
            case 'database':
                $this->app->bind(RepositoryInterface::class, LaravelDatabaseRepository::class);
                break;
            case 'file':
                $this->app->bind(RepositoryInterface::class, LaravelFileRepository::class);
                break;
            default:
                $this->app->bind(RepositoryInterface::class, LaravelFileRepository::class);
        }
    }
}
