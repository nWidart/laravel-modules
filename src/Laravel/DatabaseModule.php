<?php

namespace Nwidart\Modules\Laravel;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Entities\ModuleEntity;
use Nwidart\Modules\Json;
use Nwidart\Modules\Process\Updater;

class DatabaseModule extends Module
{
    /**
     * @var array
     */
    public $attributes;

    /**
     * @return ModuleEntity
     */
    public function getModel()
    {
        return new ModuleEntity();
    }

    /**
     * Load attributes.
     *
     * @return mixed
     * @throws Exception
     */
    private function loadAttributes()
    {
        if (!$this->getAttributes()) {

            // Try to get from cache first.
            $attributes = [];
            if (config('modules.cache.enabled')) {
                if ($modules = cache()->get(config('modules.cache.key'))) {
                    foreach ($modules as $module) {
                        if ($this->getName() == $module['name']) {
                            $attributes = $module;
                        }
                    }
                }
            }

            // Find from database. Throw error if still not found.
            if (!isset($attributes['is_active'])) {
                $attributes = $this->getModel()->where('name', $this->getName())->firstOrFail()->toArray();
            }

            $this->setAttributes($attributes);
        }

        return $this->attributes;
    }

    /**
     * Get attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set attributes.
     *
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Get a specific data from json file by given the key.
     *
     * @param string $key
     * @param null   $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return isset($this->attributes[$key]) ? $this->attributes[$key] : $default;
    }

    /**
     * Determine whether the given status same with the current module status.
     *
     * @param bool $status
     *
     * @return bool
     */
    public function isStatus(bool $status): bool
    {
        return $this->isEnabled();
    }

    /**
     * Determine whether the current module activated.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->attributes['is_active'];
    }

    /**
     *  Determine whether the current module not disabled.
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return !$this->isEnabled();
    }

    /**
     * Set active state for current module.
     *
     * @param bool $active
     *
     * @return void
     */
    public function setActive(bool $active): void
    {
        $this->getModel()->where(['name' => $this->getName()])->update(['is_active' => $active]);
        $this->flushCache();
    }

    /**
     * Disable the current module.
     */
    public function disable(): void
    {
        $this->fireEvent('disabling');

        $this->getModel()->where(['name' => $this->getName()])->update(['is_active' => 0]);
        $this->flushCache();

        $this->fireEvent('disabled');
    }

    /**
     * Enable the current module.
     */
    public function enable(): void
    {
        $this->fireEvent('enabling');

        $this->getModel()->where(['name' => $this->getName()])->update(['is_active' => 1]);
        $this->flushCache();

        $this->fireEvent('enabled');
    }

    /**
     * Delete the current module.
     *
     * @return bool
     * @throws Exception
     */
    public function delete(): bool
    {
        $module = $this->getModel()->where(['name' => $this->getName()])->first();
        if ($module) {
            $module->delete();
        }
        $this->flushCache();

        return (new Filesystem())->deleteDirectory($this->getPath());
    }

    /**
     * @return mixed|null
     */
    public function getVersion()
    {
        return $this->get('version', '1.0.0');
    }

    public function update(Updater $updater)
    {

        if (config('modules.database_management.update_file_to_database_when_updating')) {
            $json = Json::make($this->getPath() . '/' . 'module.json');
            $data = $json->getAttributes();

            if (!isset($data['version'])) {
                $data['version'] = '1.0.0';
            }

            // Check version, if version is higher then update module.json into database.
            if (version_compare($this->getVersion(), $data['version'], '<=')) {
                $data = resolve(RepositoryInterface::class)->validateAttributes($data);
                $this->getModel()->where(['name' => $data['name']])->update($data);
            }
        }

        $response = with($updater)->update($this->getName());
        $this->flushCache();

        return $response;
    }
}
