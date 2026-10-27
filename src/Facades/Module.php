<?php

namespace Nwidart\Modules\Facades;

use Illuminate\Support\Facades\Facade;
use Nwidart\Modules\Commands\Database\MigrateFreshCommand;
use Nwidart\Modules\Commands\Database\MigrateRefreshCommand;
use Nwidart\Modules\Commands\Database\MigrateResetCommand;

/**
 * @method static array<string, \Nwidart\Modules\Module> all()
 * @method static array<string, \Nwidart\Modules\Module> getCached()
 * @method static array<string, \Nwidart\Modules\Module> scan()
 * @method static \Nwidart\Modules\Collection<string, \Nwidart\Modules\Module> toCollection()
 * @method static array<int, string> getScanPaths()
 * @method static array<string, \Nwidart\Modules\Module> allEnabled()
 * @method static array<string, \Nwidart\Modules\Module> allDisabled()
 * @method static int count()
 * @method static array<string, \Nwidart\Modules\Module> getOrdered($direction = 'asc')
 * @method static array<string, \Nwidart\Modules\Module> getByStatus($status)
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

    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return 'modules';
    }
}
