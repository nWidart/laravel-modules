<?php

namespace Nwidart\Modules\Laravel;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Entities\ModuleEntity;

class DatabaseModule extends Module
{
    /**
     * @var array<string, mixed>
     */
    public $attributes;

    /**
     * @return ModuleEntity
     */
    public function getModel(): ModuleEntity
    {
        return new ModuleEntity();
    }

    /**
     * Get attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set attributes.
     *
     * @param array $attributes
     * @return void
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * Get a specific data from json file by given the key.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
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
        parent::setActive($active);
        $this->getModel()->where(['name' => $this->getName()])->update(['is_active' => $active]);
        $this->flushCache();
    }

    /**
     * Disable the current module.
     */
    public function disable(): void
    {
        $this->fireEvent('disabling');

        $this->setActive(false);

        $this->fireEvent('disabled');
    }

    /**
     * Enable the current module.
     */
    public function enable(): void
    {
        $this->fireEvent('enabling');

        $this->setActive(true);

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
     * Get version.
     *
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->get('version', config('modules.default_version'));
    }
}
