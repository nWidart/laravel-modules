<?php
/**
 * Extend Laravel's ServiceProvider to handle config file hierarchies  
 * 
 * If a module has required packages, you m,ight have a hierarchy of
 * config files e.g.
 * 1. Config/package.php
 * 2. Modules/module1/Config/package.php
 * 3. vendor/package/config/config.php
 * 
 * The normal Laravel `mergeConfigFrom` method does not handle this.
 * This trait provides an additional method to handle this functionality
 * 
 * Usage:
 * 
 * use Illuminate\Support\ServiceProvider;
 * use Nwidart\Modules\Laravel\ModuleConfigMerge;
 *
 * class DeveloperServiceProvider extends ServiceProvider
 * {
 *     use ModuleConfigMerge;
 * ...
 *     protected function registerConfig()
 *     {
 * ... 
 *         $this->mergeModuleConfig('debugbar.php', 'debugbar');
 * ... 
 *     }
 * }
 */

namespace Nwidart\Modules\Laravel;

trait ModuleConfigMerge {
{
    /**
     * Handle hierarchy of app, module and package configs
     * 
     * @param string $file - name of configuration file
     * @param string $key - configuration key name
     * @return void
     */
    protected function mergeModuleConfig(string $file, string $key): void
    {
        $mod_config = module_path($this->moduleName, 'Config/' . $file);
        if (!file_exists($mod_config)) {
            return;
        }

        /* Unclear if package has already merged the config so we need to remerge as follows:
            1. Existing values
            2. Overridden by module config 
            3. Overriden by app config IF IT EXISTS
        */

        // Following code based on Illuminate\Support\ServiceProvider::mergeConfigFrom()
        if (! ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached())) {
            $config = $this->app->make('config');
            $values = array_merge(
                $config->get($key, []),
                require $mod_config
            );

            $app_config = config_path($file);
            if (file_exists($app_config)) {
                $values = array_merge(
                    $values,
                    require $app_config
                );
            }

            $config->set($key, $values);
        }
    }
}
