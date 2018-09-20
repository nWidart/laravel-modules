<?php return '<?php

namespace Modules\\Blog\\Providers;

use Illuminate\\Support\\Facades\Route;
use Illuminate\\Foundation\\Support\\Providers\\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     *
     * @return void
     */
    public function boot()
    {
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::prefix(\'blog\')
            ->middleware(\'web\')
            ->namespace(\'Modules\\Blog\\Http\\Controllers\')
            ->group(__DIR__ . \'/../Routes/web.php\');
    }
}
';