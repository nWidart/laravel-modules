<?php return '<?php

namespace Modules\\Blog\\SuperProviders;

use Illuminate\\Support\\ServiceProvider;
use Illuminate\\Database\\Eloquent\\Factory;
use Illuminate\\Support\\Facades\\File;

class BlogServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = \'Blog\';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = \'blog\';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(module_path($this->moduleName, \'Database/Migrations\'));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {      
        $files = $this->getConfigFiles();
        foreach($files as $file){
            if($file->isFile() && $file->getExtension() == "php"){
                $this->registerConfigFile($file->getPathname(), $file->getFilename());
            }
        }
    }   

    /**
     * Register config.
     * 
     * @return void 
     */
    protected function registerConfigFile($path, $fileName){
         
        $this->publishes([
            $path => config_path($this->moduleNameLower."::".$fileName),
        ], \'config\');
         
        $this->mergeConfigFrom(
            $path, $this->moduleNameLower."::".head(explode(".",$fileName))
        );
    }

    /**
     * Get all the files of module Config folder
     * 
     * @return array 
     */
    protected function getConfigFiles(){ 
        return File::files(module_path($this->moduleName, \'Config\'));
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path(\'views/modules/\' . $this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, \'Resources/views\');

        $this->publishes([
            $sourcePath => $viewPath
        ], [\'views\', $this->moduleNameLower . \'-module-views\']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path(\'lang/modules/\' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, \'Resources/lang\'), $this->moduleNameLower);
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment(\'production\') && $this->app->runningInConsole()) {
            app(Factory::class)->load(module_path($this->moduleName, \'Database/factories\'));
        }
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

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\\Config::get(\'view.paths\') as $path) {
            if (is_dir($path . \'/modules/\' . $this->moduleNameLower)) {
                $paths[] = $path . \'/modules/\' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
';
