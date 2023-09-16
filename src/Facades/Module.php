<?php

namespace Nwidart\Modules\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method  array all()
 * @method  array getCached()
 * @method  array scan()
 * @method  \Nwidart\Modules\Collection toCollection()
 * @method  array getScanPaths()
 * @method  array allEnabled()
 * @method  array allDisabled()
 * @method  int count()
 * @method  array getOrdered($direction = 'asc')
 * @method  array getByStatus($status)
 * @method  \Nwidart\Modules\Module find(string $name)
 * @method  \Nwidart\Modules\Module findOrFail(string $name)
 * @method  string getModulePath($moduleName)
 * @method  \Illuminate\Filesystem\Filesystem getFiles()
 * @method  mixed config(string $key, $default = NULL)
 * @method  string getPath()
 * @method  void boot()
 * @method  void register(): void
 * @method  string assetPath(string $module)
 * @method  bool delete(string $module)
 * @method  bool isEnabled(string $name)
 * @method  bool isDisabled(string $name)
 */
class Module extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'modules';
    }
}
