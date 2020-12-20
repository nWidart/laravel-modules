<?php

namespace Nwidart\Modules\Livewire;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Support\Config\GenerateConfigReader;

class LivewireServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->loadComponents();
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

    protected function loadComponents()
    {
        if (! class_exists('Livewire')) return false;

        $modules = \Module::toCollection();

        $defaultNamespace = GenerateConfigReader::read('livewire-namespace')->getPath();
        
        $filesystem = new Filesystem();

        $modules->map(function ($module) use ($filesystem, $defaultNamespace) {
            $modulePath = $module->getPath();

            $path = $modulePath. '/'. strtr($defaultNamespace, ['\\' => '/']);

            $files = collect( $filesystem->isDirectory($path) ? $filesystem->allFiles($path) : [] );

            $files->map(function ($file) use ($module, $path, $defaultNamespace) {
                $componentPath = \Str::after($file->getPathname(), $path.'/');

                $componentClassPath = strtr( $componentPath , ['/' => '\\', '.php' => '']);
        
                $componentName = $this->getComponentName($componentClassPath, $module);

                $componentClassStr = '\\' . config('modules.namespace') . '\\' . $module->getName() . '\\' . $defaultNamespace . '\\' . $componentClassPath;

                $componentClass = get_class(new $componentClassStr);

                $loadComponent = \Livewire::component($componentName, $componentClass);
            });
        });
    }

    protected function getComponentName($componentClassPath, $module = null)
    {
        $dirs = explode('\\', $componentClassPath);

        $componentName = collect($dirs)
            ->map([\Str::class, 'kebab'])
            ->implode('.');

        $moduleNamePrefix = ($module) ? $module->getLowerName().'::' : null;

       return $moduleNamePrefix . $componentName;
    }
}