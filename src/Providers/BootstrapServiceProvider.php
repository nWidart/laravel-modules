<?php

namespace Nwidart\Modules\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Laravel\LaravelFileRepository;

class BootstrapServiceProvider extends ServiceProvider
{
    /**
     * Booting the package.
     */
    public function boot(): void
    {
        $this->getRepositoryInterface()->boot();
    }

    /**
     * Register the provider.
     */
    public function register(): void
    {
        $this->getRepositoryInterface()->register();
    }

    /**
     * Get Repository Interface
     */
    public function getRepositoryInterface(): LaravelFileRepository
    {
        return $this->app[RepositoryInterface::class];
    }
}
