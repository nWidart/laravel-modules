<?php

namespace Nwidart\Modules\Facades;

use Illuminate\Support\Facades\Facade;
use Nwidart\Modules\Commands\Database\MigrateFreshCommand;
use Nwidart\Modules\Commands\Database\MigrateRefreshCommand;
use Nwidart\Modules\Commands\Database\MigrateResetCommand;

/**
 * @method static array all()
 * @method static array getCached()
 * @method static array scan()
 * @method static \Nwidart\Modules\Collection toCollection()
 * @method static array getScanPaths()
 * @method static array allEnabled()
 * @method static array allDisabled()
 * @method static int count()
 * @method static array getOrdered($direction = 'asc')
 * @method static array getByStatus($status)
 * @method static \Nwidart\Modules\Module find(string $name)
 * @method static \Nwidart\Modules\Module findOrFail(string $name)
 * @method static string getModulePath($moduleName)
 * @method static \Illuminate\Filesystem\Filesystem getFiles()
 * @method static mixed config(string $key, $default = null)
 * @method static string getPath()
 * @method static void boot()
 * @method static void register(): void
 * @method static string assetPath(string $module)
 * @method static bool delete(string $module)
 * @method static bool isEnabled(string $name)
 * @method static bool isDisabled(string $name)
 */
class Module extends Facade
{
    /**
     * Indicate if destructive Artisan commands should be prohibited.
     *
     * Prohibits: module:migrate-fresh, module:migrate-refresh, and module:migrate-reset
     */
    public static function prohibitDestructiveCommands(bool $prohibit = true): void
    {
        MigrateFreshCommand::prohibit($prohibit);
        MigrateRefreshCommand::prohibit($prohibit);
        MigrateResetCommand::prohibit($prohibit);
    }

    protected static function getFacadeAccessor(): string
    {
        return 'modules';
    }
}
