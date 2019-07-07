<?php

namespace Nwidart\Modules;

use Illuminate\Container\Container;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Module;

class FileActivator implements ActivatorInterface
{
	protected $cacheKey = 'modules.activator.installed';
	protected $installed, $fileInstalled;

	public function __construct(Container $app)
	{
		$this->fileInstalled = storage_path('installed_modules');
		$this->cache = $app['cache'];
		$this->files = $app['files'];
		$this->config = $app['config'];
		$this->installed = $this->getCached();
	}

	/**
	 * Get installed cache
	 * 
	 * @return array
	 */
	protected function getCached()
	{
		if(!$this->config('cache.enabled')) return $this->readJson();
		
		return $this->cache->remember($this->cacheKey, $this->config('cache.lifetime'), function () {
            return $this->readJson();
        });
	}

	/**
	 * Forgets the installed cache
	 */
	protected function forgetCache()
	{
		$this->cache->forget($this->cacheKey);
	}

	/**
	 * Reads a config parameter
	 * 
	 * @param  string $key     [description]
	 * @param  $default
	 * @return mixed
	 */
	protected function config(string $key, $default = null)
    {
        return $this->config->get('modules.' . $key, $default);
    }

	/**
	 * Reads the installed json file
	 * 
	 * @return array
	 */
	protected function readJson()
	{
		if(!$this->files->exists($this->fileInstalled)) return [];
		return json_decode($this->files->get($this->fileInstalled), true);
	}

	/**
	 * Writes the installed json file
	 */
	protected function writeJson()
	{
		$this->files->put($this->fileInstalled, json_encode($this->installed, JSON_PRETTY_PRINT));
	}

	/**
     * @inheritDoc
     */
    public function enable(Module $module)
    {
    	$this->setActiveByName($module->getName(), 1);
    }

    /**
     * @inheritDoc
     */
    public function disable(Module $module)
    {
		$this->setActiveByName($module->getName(), 0);
    }

    /**
     * @inheritDoc
     */
    public function isStatus(Module $module, $status)
    {
    	if(!isset($this->installed[$module->getName()])) return false;
    	return $this->installed[$module->getName()] == $status;
    }

    /**
     * @inheritDoc
     */
    public function setActive(Module $module, $active)
    {
    	$this->setActiveByName($module->getName(), $active);
    }

    /**
     * @inheritDoc
     */
    public function setActiveByName(string $name, $status)
    {
    	$this->installed[$name] = (int)$status;
    	$this->writeJson();
    	$this->forgetCache();
    }

    /**
     * @inheritDoc
     */
    public function delete(Module $module)
    {
    	if(!isset($this->installed[$module->getName()])) return;
    	unset($this->installed[$module->getName()]);
    	$this->writeJson();
    	$this->forgetCache();
    }
}