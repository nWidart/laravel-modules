<?php

namespace Nwidart\Modules\Support;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Support\Str;
use SplFileInfo;

/**
 * Guesses listener class names during Laravel event discovery, so listeners inside modules are found.
 *
 * Laravel builds the class name from the file path relative to the base path, which turns
 * "Modules/Blog/app/Listeners/SyncPoints.php" into "Modules\Blog\app\Listeners\SyncPoints".
 * Module composer.json files map "Modules\Blog\" to the app folder, so the real class is
 * "Modules\Blog\Listeners\SyncPoints". Files outside the modules directory are passed to the
 * callback that was set before this one, or to Laravel's default logic.
 *
 * Config and the modules path are read on the first call only, so registering costs nothing
 * and the callback runs only while events are discovered (never when the event cache is used).
 *
 * @phpstan-consistent-constructor
 */
class ListenerClassNameGuesser
{
    /**
     * The callback that was set before this one, used for files outside the modules directory.
     *
     * @var (callable(SplFileInfo, string): string)|null
     */
    protected $previous;

    /**
     * Whether the config below was read.
     */
    protected bool $resolved = false;

    /**
     * Real path of the modules directory, with a trailing separator. Null when it does not exist.
     */
    protected ?string $modulesPath = null;

    /**
     * The modules namespace, with a trailing backslash (or empty).
     */
    protected string $namespace = '';

    /**
     * The app folder prefix to strip, with a trailing separator (or empty).
     */
    protected string $appFolder = '';

    /**
     * @param  (callable(SplFileInfo, string): string)|null  $previous
     */
    public function __construct(?callable $previous = null)
    {
        $this->previous = $previous;
    }

    /**
     * Register the guesser on Laravel's event discovery.
     *
     * A callback set earlier is kept and used for files outside the modules directory.
     * A guesser registered by an earlier application instance (tests) is replaced, not chained.
     */
    public static function register(): void
    {
        $previous = DiscoverEvents::$guessClassNamesUsingCallback;

        if ($previous instanceof self) {
            $previous = $previous->previous;
        }

        DiscoverEvents::guessClassNamesUsing(new static($previous));
    }

    /**
     * Guess the class name of the given listener file.
     */
    public function __invoke(SplFileInfo $file, string $basePath): string
    {
        if (! $this->resolved) {
            $this->resolve();
        }

        $path = (string) $file->getRealPath();

        if ($this->modulesPath !== null && str_starts_with($path, $this->modulesPath)) {
            $class = $this->moduleClass(substr($path, strlen($this->modulesPath)));

            if ($class !== null) {
                return $class;
            }
        }

        if ($this->previous !== null) {
            return ($this->previous)($file, $basePath);
        }

        return static::laravelClassFromFile($file, $basePath);
    }

    /**
     * Build the class name from a path relative to the modules directory: "Blog/app/Listeners/SyncPoints.php".
     */
    protected function moduleClass(string $relative): ?string
    {
        $separator = strpos($relative, DIRECTORY_SEPARATOR);

        if ($separator === false) {
            return null;
        }

        $module = substr($relative, 0, $separator);
        $path = substr($relative, $separator + 1);

        if ($this->appFolder !== '' && str_starts_with($path, $this->appFolder)) {
            $path = substr($path, strlen($this->appFolder));
        }

        if (str_ends_with($path, '.php')) {
            $path = substr($path, 0, -4);
        }

        return $this->namespace.$module.'\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $path);
    }

    /**
     * Read the modules path, namespace, and app folder once.
     */
    protected function resolve(): void
    {
        $this->resolved = true;

        $config = app(ConfigRepository::class);

        $modulesPath = realpath((string) $config->get('modules.paths.modules'));
        $this->modulesPath = $modulesPath === false ? null : rtrim($modulesPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        $namespace = trim((string) $config->get('modules.namespace', 'Modules'), '\\');
        $this->namespace = $namespace === '' ? '' : $namespace.'\\';

        // Same rule as PathNamespace::strip_app_folder(): only a whole leading segment is removed.
        $appFolder = trim((string) $config->get('modules.paths.app_folder'), '/');
        $this->appFolder = $appFolder === '' ? '' : str_replace('/', DIRECTORY_SEPARATOR, $appFolder).DIRECTORY_SEPARATOR;
    }

    /**
     * Laravel's default logic, copied from DiscoverEvents::classFromFile() (protected).
     */
    public static function laravelClassFromFile(SplFileInfo $file, string $basePath): string
    {
        $class = trim(Str::replaceFirst($basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

        return ucfirst(Str::camel(str_replace(
            [DIRECTORY_SEPARATOR, ucfirst(basename(app()->path())).'\\'],
            ['\\', app()->getNamespace()],
            ucfirst(Str::replaceLast('.php', '', $class))
        )));
    }
}
