<?php
declare(strict_types=1);

namespace Nwidart\Modules\Contracts;

use Nwidart\Modules\Json;

interface ModuleInterface
{
    /**
     * Get laravel instance.
     * @return \Illuminate\Contracts\Foundation\Application|\Laravel\Lumen\Application
     */
    public function getLaravel();

    /**
     * Get name.
     * @return string
     */
    public function getName(): string;

    /**
     * Get name in lower case.
     * @return string
     */
    public function getLowerName(): string;

    /**
     * Get name in studly case.
     * @return string
     */
    public function getStudlyName(): string;

    /**
     * Get name in snake case.
     * @return string
     */
    public function getSnakeName(): string;

    /**
     * Get description.
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get alias.
     * @return string
     */
    public function getAlias(): string;

    /**
     * Get priority.
     * @return string
     */
    public function getPriority(): string;

    /**
     * Get module requirements.
     * @return array
     */
    public function getRequires(): array;

    /**
     * Get path.
     * @return string
     */
    public function getPath(): string;

    /**
     * Set path.
     * @param string $path
     * @return $this
     */
    public function setPath($path);

    /**
     * Bootstrap the application events.
     */
    public function boot(): void;

    /**
     * Get json contents from the cache, setting as needed.
     * @param string $file
     * @return Json
     */
    public function json($file = null): Json;

    /**
     * Get a specific data from json file by given the key.
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Get a specific data from composer.json file by given the key.
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function getComposerAttr($key, $default = null);

    /**
     * Register the module.
     */
    public function register(): void;

    /**
     * Register the aliases from this module.
     */
    public function registerAliases(): void;

    /**
     * Register the service providers from this module.
     */
    public function registerProviders(): void;

    /**
     * Get the path to the cached *_module.php file.
     * @return string
     */
    public function getCachedServicesPath(): string;

    /**
     * Determine whether the given status same with the current module status.
     * @param int $status
     * @return bool
     */
    public function isStatus(int $status): bool;

    /**
     * Determine whether the current module activated.
     * @return bool
     */
    public function enabled(): bool;

    /**
     *  Determine whether the current module not disabled.
     * @return bool
     */
    public function disabled(): bool;

    /**
     * Set active state for current module.
     * @param int $active
     * @return bool
     */
    public function setActive(int $active): bool ;

    /**
     * Disable the current module.
     */
    public function disable(): void;

    /**
     * Enable the current module.
     */
    public function enable(): void;

    /**
     * Delete the current module.
     * @return bool
     */
    public function delete(): bool;

    /**
     * Get extra path.
     * @param string $path
     * @return string
     */
    public function getExtraPath(string $path): string;
}
