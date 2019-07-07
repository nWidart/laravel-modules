<?php

namespace Nwidart\Modules\Activators;

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Module;

class FileActivator implements ActivatorInterface
{
	/**
	 * Laravel cache instance
	 * 
	 * @var CacheManager
	 */
	protected $cache;

	/**
	 * Laravel Filesystem instance
	 * 
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * Laravel config instance
	 * @var Config
	 */
	protected $config;

	/**
	 * @var string
	 */
	protected $cacheKey;

	/**
	 * @var string
	 */
	protected $cacheLifetime;

	/**
	 * Array of modules activation statuses
	 * 
	 * @var array
	 */
	protected $installed;

	/**
	 * File used to store activation statuses
	 * 
	 * @var string
	 */
	protected $fileInstalled;

	public function __construct(Container $app)
	{
		$this->cache = $app['cache'];
		$this->files = $app['files'];
		$this->config = $app['config'];
		$this->fileInstalled = $this->config('file');
		$this->cacheKey = $this->config('cache-key');
		$this->cacheLifetime = $this->config('cache-lifetime');
		$this->installed = $this->getCached();
	}

	/**
	 * Get installed cache
	 * 
	 * @return array
	 */
	protected function getCached()
	{
		if(!$this->config->get('modules.cache.enabled')) return $this->readJson();
		
		return $this->cache->remember($this->cacheKey, $this->cacheLifetime, function () {
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
        return $this->config->get('modules.activators.file.' . $key, $default);
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
