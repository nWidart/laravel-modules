<?php

namespace Nwidart\Modules;

use Illuminate\Container\Container;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;

class Module extends ServiceProvider
{
    use Macroable;

    /**
     * The laravel|lumen application instance.
     *
     * @var Illuminate\Contracts\Foundation\Application|Laravel\Lumen\Application
     */
    protected $app;

    /**
     * The module name.
     *
     * @var
     */
    protected $name;

    /**
     * The module path.
     *
     * @var string
     */
    protected $path;

    /**
     * @var array of cached Json objects, keyed by filename
     */
    protected $moduleJson = [];

    /**
     * The constructor.
     *
     * @param Container $app
     * @param $name
     * @param $path
     */
    public function __construct(Container $app, $name, $path)
    {
        parent::__construct($app);
        $this->name = $name;
        $this->path = realpath($path);
    }

    /**
     * Get laravel instance.
     *
     * @return Illuminate\Contracts\Foundation\Application|Laravel\Lumen\Application
     */
    public function getLaravel()
    {
        return $this->app;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get name in lower case.
     *
     * @return string
     */
    public function getLowerName()
    {
        return strtolower($this->name);
    }

    /**
     * Get name in studly case.
     *
     * @return string
     */
    public function getStudlyName()
    {
        return Str::studly($this->name);
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->get('description');
    }

    /**
     * Get alias.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->get('alias');
    }

    /**
     * Get priority.
     *
     * @return string
     */
    public function getPriority()
    {
        return $this->get('priority');
    }

    /**
     * Get module requirements.
     *
     * @return array
     */
    public function getRequires()
    {
        return $this->get('requires');
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set path.
     *
     * @param string $path
     *
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        if (config('modules.register.translations', true) === true) {
            $this->registerTranslation();
        }

        $this->fireEvent('boot');
    }

    /**
     * Register module's translation.
     *
     * @return void
     */
    protected function registerTranslation()
    {
        $lowerName = $this->getLowerName();

        $langPath = $this->getPath() . "/Resources/lang";

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $lowerName);
        }
    }

    /**
     * Get json contents from the cache, setting as needed.
     *
     * @param $file
     *
     * @return Json
     */
    public function json($file = null)
    {
        if ($file === null) {
            $file = 'module.json';
        }

        return array_get($this->moduleJson, $file, function () use ($file) {
            return $this->moduleJson[$file] = new Json($this->getPath() . '/' . $file, $this->app['files']);
        });
    }

    /**
     * Get a specific data from json file by given the key.
     *
     * @param $key
     * @param null $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->json()->get($key, $default);
    }

    /**
     * Get a specific data from composer.json file by given the key.
     *
     * @param $key
     * @param null $default
     *
     * @return mixed
     */
    public function getComposerAttr($key, $default = null)
    {
        return $this->json('composer.json')->get($key, $default);
    }

    /**
     * Register the module.
     */
    public function register()
    {
        $this->registerAliases();

        $this->registerProviders();

        $this->registerFiles();

        $this->fireEvent('register');
    }

    /**
     * Register the module event.
     *
     * @param string $event
     */
    protected function fireEvent($event)
    {
        $this->app['events']->fire(sprintf('modules.%s.' . $event, $this->getLowerName()), [$this]);
    }

    /**
     * Register the aliases from this module.
     */
    protected function registerAliases()
    {
        $loader = AliasLoader::getInstance();
        foreach ($this->get('aliases', []) as $aliasName => $aliasClass) {
            $loader->alias($aliasName, $aliasClass);
        }
    }

    /**
     * Register the service providers from this module.
     */
    protected function registerProviders()
    {
        foreach ($this->get('providers', []) as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * Register the files from this module.
     */
    protected function registerFiles()
    {
        foreach ($this->get('files', []) as $file) {
            include $this->path . '/' . $file;
        }
    }

    /**
     * Handle call __toString.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getStudlyName();
    }

    /**
     * Determine whether the given status same with the current module status.
     *
     * @param $status
     *
     * @return bool
     */
    public function isStatus($status)
    {
        return $this->get('active', 0) === $status;
    }

    /**
     * Determine whether the current module activated.
     *
     * @return bool
     */
    public function enabled()
    {
        return $this->active();
    }

    /**
     * Alternate for "enabled" method.
     *
     * @return bool
     */
    public function active()
    {
        return $this->isStatus(1);
    }

    /**
     * Determine whether the current module not activated.
     *
     * @return bool
     */
    public function notActive()
    {
        return !$this->active();
    }

    /**
     * Alias for "notActive" method.
     *
     * @return bool
     */
    public function disabled()
    {
        return !$this->enabled();
    }

    /**
     * Set active state for current module.
     *
     * @param $active
     *
     * @return bool
     */
    public function setActive($active)
    {
        return $this->json()->set('active', $active)->save();
    }

    /**
     * Disable the current module.
     */
    public function disable()
    {
        $this->fireEvent('disabling');

        $this->setActive(0);

        $this->fireEvent('disabled');
    }

    /**
     * Enable the current module.
     */
    public function enable()
    {
        $this->fireEvent('enabling');

        $this->setActive(1);

        $this->fireEvent('enabled');
    }

    /**
     * Delete the current module.
     *
     * @return bool
     */
    public function delete()
    {
        return $this->json()->getFilesystem()->deleteDirectory($this->getPath());
    }

    /**
     * Get extra path.
     *
     * @param $path
     *
     * @return string
     */
    public function getExtraPath($path)
    {
        return $this->getPath() . '/' . $path;
    }

    /**
     * Handle call to __get method.
     *
     * @param $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }
}
