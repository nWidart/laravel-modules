<?php

namespace Modules\Recipe\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Recipe\Entities\Recipe;
use Modules\Recipe\Repositories\Cache\CacheRecipeDecorator;
use Modules\Recipe\Repositories\Eloquent\EloquentRecipeRepository;

class RecipeServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerBindings();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    private function registerBindings()
    {
        $this->app->bind(
            'Modules\Recipe\Repositories\RecipeRepository',
            function () {
                $repository = new EloquentRecipeRepository(new Recipe);

                if (! config('app.cache')) {
                    return $repository;
                }

                return new CacheRecipeDecorator($repository);
            }
        );
        // add bindings
    }
}
