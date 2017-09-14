<?php

namespace Nwidart\Modules\Laravel;

use Nwidart\Modules\Json;
use Nwidart\Modules\Repository as BaseRepository;

class Repository extends BaseRepository
{
    /**
     * {@inheritdoc}
     */
    public function scan()
    {
        $paths = $this->getScanPaths();

        $modules = [];

        foreach ($paths as $key => $path) {
            $manifests = $this->app['files']->glob("{$path}/module.json");

            is_array($manifests) || $manifests = [];

            foreach ($manifests as $manifest) {
                $name = Json::make($manifest)->get('name');

                $modules[$name] = new Module($this->app, $name, dirname($manifest));
            }
        }

        return $modules;
    }

    /**
     * {@inheritdoc}
     */
    protected function formatCached($cached)
    {
        $modules = [];

        foreach ($cached as $name => $module) {
            $path = $this->config('paths.modules') . '/' . $name;

            $modules[$name] = new Module($this->app, $name, $path);
        }

        return $modules;
    }
}
