<?php

namespace Nwidart\Modules\Migrations;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\Migrator as LaravelMigrator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nwidart\Modules\Module;
use Nwidart\Modules\Support\Config\GenerateConfigReader;

class Migrator
{
    /**
     * Module instance.
     *
     * @var Module
     */
    protected $module;

    /**
     * Laravel Application instance.
     *
     * @var Application
     */
    protected $laravel;

    /**
     * Optional subpath for specific migration file.
     *
     * @var string|null
     *
     * @example subpath 2000_01_01_000000_create_example_table.php
     */
    protected $subpath = '';

    /**
     * The migration files found by getMigrations(), keyed by migration name.
     *
     * @var array<string, string>
     */
    protected $migrationFiles = [];

    /**
     * The database connection to be used
     *
     * @var string
     */
    protected $database = '';

    /**
     * Create new instance.
     *
     * @param  string|null  $subpath
     */
    public function __construct(Module $module, Application $application, $subpath = null)
    {
        $this->module = $module;
        $this->laravel = $application;
        $this->subpath = $subpath;
    }

    /**
     * Set the database connection to be used
     * A value that is not a non-empty string keeps the current database.
     *
     * @param  string|null  $database
     * @return $this
     */
    public function setDatabase($database)
    {
        if (is_string($database) && $database) {
            $this->database = $database;
        }

        return $this;
    }

    /**
     * @return Module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Get migration path.
     *
     * @return string
     */
    public function getPath()
    {
        $config = $this->module->get('migration');

        $migrationPath = GenerateConfigReader::read('migration');
        $path = (is_array($config) && array_key_exists('path', $config)) ? $config['path'] : $migrationPath->getPath();

        return $this->module->getExtraPath($path);
    }

    /**
     * Get the path the migrations are read from: the migrations directory, or the subpath inside it.
     */
    public function getMigrationsPath(): string
    {
        if (empty($this->subpath)) {
            return $this->getPath();
        }

        return rtrim($this->getPath(), '/\\').'/'.ltrim($this->subpath, '/\\');
    }

    /**
     * Whether the subpath is a directory or matches at least one file. Always true without a subpath.
     */
    public function subpathExists(): bool
    {
        if (empty($this->subpath)) {
            return true;
        }

        $path = $this->getMigrationsPath();

        return is_dir($path) || ! empty($this->laravel->make(Filesystem::class)->glob($path));
    }

    /**
     * Get every migration path of the module.
     *
     * The configured migrations directory comes first, then each path registered on Laravel's
     * migrator (auto-discovery, loadMigrationsFrom() in the module providers) inside the module.
     *
     * @return array<int, string>
     */
    public function getPaths(): array
    {
        $modulePath = rtrim($this->module->getPath(), '/\\');

        // Migrator::paths() is documented as string[], but Migrator::path() stores any value it is given.
        /** @var array<int, mixed> $paths */
        $paths = $this->laravelMigrator()->paths();

        $registered = array_filter(
            $paths,
            fn ($path) => is_string($path) && Str::startsWith($path, [$modulePath.'/', $modulePath.DIRECTORY_SEPARATOR])
        );

        return array_values(array_unique([$this->getPath(), ...$registered]));
    }

    /**
     * Get Laravel's migrator.
     *
     * Resolved by its 'migrator' key: Migrator::class is a non-shared binding to that key, so
     * resolving the class would fire the Migrator resolving callbacks again on every call.
     *
     * @return LaravelMigrator
     */
    private function laravelMigrator()
    {
        return $this->laravel->make('migrator');
    }

    /**
     * Get migration files.
     *
     * Without a subpath, the files of every path of getPaths() are used. The subpath, relative
     * to the migrations directory, is a directory, a single file, or a glob pattern.
     *
     * @param  bool  $reverse
     * @return array<int, string>
     */
    public function getMigrations($reverse = false)
    {
        $files = [];

        foreach ($this->getMigrationFiles() as $file) {
            $name = str_replace('.php', '', basename($file));

            // remember where the file is, it may be in a subdirectory or in another registered path
            $this->migrationFiles[$name] = $file;

            $files[$name] = $name;
        }

        $files = array_values($files);

        // Once we have all of the formatted file names we will sort them and since
        // they all start with a timestamp this should give us the migrations in
        // the order they were actually created by the application developers.
        sort($files);

        if ($reverse) {
            return array_reverse($files);
        }

        return $files;
    }

    /**
     * Get the migration files of the subpath, or else of every path of getPaths().
     *
     * @return array<int, string>
     */
    protected function getMigrationFiles(): array
    {
        $files = $this->laravel->make(Filesystem::class);

        if (! empty($this->subpath)) {
            $path = $this->getMigrationsPath();

            return $files->glob(is_dir($path) ? $path.'/*_*.php' : $path) ?: [];
        }

        // like Laravel's migrator, a path is a directory of migrations or a single migration file
        $migrationFiles = [];

        foreach ($this->getPaths() as $path) {
            array_push($migrationFiles, ...(str_ends_with($path, '.php') ? [$path] : ($files->glob($path.'/*_*.php') ?: [])));
        }

        return $migrationFiles;
    }

    /**
     * Get the file path of the given migration name.
     */
    protected function getMigrationFilePath(string $migration): string
    {
        if (! isset($this->migrationFiles[$migration])) {
            $this->getMigrations();
        }

        return $this->migrationFiles[$migration] ?? $this->getPath().'/'.$migration.'.php';
    }

    /**
     * Rollback the last batch of migrations.
     *
     * Without $pretend, returns the names of the rolled back migrations. With $pretend,
     * nothing is run or deleted, and the SQL each down() would run is returned instead,
     * keyed by migration name.
     *
     * @return array<int, string>|array<string, array<int, string>>
     */
    public function rollback(bool $pretend = false)
    {
        return $this->rollbackMigrations($this->getLast($this->getMigrations(true))->toArray(), $pretend);
    }

    /**
     * Reset all the migrations.
     *
     * Without $pretend, returns the names of the rolled back migrations. With $pretend,
     * nothing is run or deleted, and the SQL each down() would run is returned instead,
     * keyed by migration name.
     *
     * @return array<int, string>|array<string, array<int, string>>
     */
    public function reset(bool $pretend = false)
    {
        return $this->rollbackMigrations($this->getMigrations(true), $pretend);
    }

    /**
     * Roll back the given migrations that have been run.
     *
     * @param  array<int, string>  $migrations
     * @return array<int, string>|array<string, array<int, string>>
     */
    protected function rollbackMigrations(array $migrations, bool $pretend)
    {
        $this->requireFiles($migrations);

        $migrated = [];

        foreach ($migrations as $migration) {
            $data = $this->find($migration);

            if (! $data->count()) {
                continue;
            }

            if ($pretend) {
                $migrated[$migration] = $this->pretendDown($migration);

                continue;
            }

            $migrated[] = $migration;

            $this->down($migration);

            $data->delete();
        }

        return $migrated;
    }

    /**
     * Run down schema from the given migration name.
     *
     * It runs on the migration's own connection, else the selected database, else the default.
     *
     * @param  string  $migration
     * @return void
     */
    public function down($migration)
    {
        $this->runMigration($this->resolve($migration), 'down');
    }

    /**
     * Get the SQL queries the down schema of the given migration would run, without running them.
     *
     * @param  string  $migration
     * @return array<int, string>
     */
    public function pretendDown($migration): array
    {
        return $this->getQueries($this->resolve($migration), 'down');
    }

    /**
     * Get all the queries that would be run for a migration method.
     *
     * The connection is made the default connection during the call, so the Schema facade pretends too.
     *
     * @param  object  $migration
     * @return array<int, string>
     */
    protected function getQueries($migration, string $method): array
    {
        $connection = $this->resolveConnection($migration);

        $queries = $connection->pretend(function () use ($connection, $migration, $method) {
            if (method_exists($migration, $method)) {
                $this->runMethod($connection, $migration, $method);
            }
        });

        return array_column($queries, 'query');
    }

    /**
     * Resolve the connection a migration runs on: its own, else the selected database, else the default.
     *
     * @param  object  $migration
     * @return Connection
     */
    protected function resolveConnection($migration)
    {
        $name = method_exists($migration, 'getConnection') ? $migration->getConnection() : null;

        return $this->laravel->make(DatabaseManager::class)->connection($name ?: ($this->database ?: null));
    }

    /**
     * Run a migration method with the given connection as the default connection.
     *
     * @param  Connection  $connection
     * @param  object  $migration
     */
    protected function runMethod($connection, $migration, string $method): void
    {
        $db = $this->laravel->make(DatabaseManager::class);

        $previousConnection = $db->getDefaultConnection();

        try {
            $db->setDefaultConnection($connection->getNameWithReadWriteType());

            $migration->{$method}();
        } finally {
            $db->setDefaultConnection($previousConnection);
        }
    }

    /**
     * Run up schema from the given migration name.
     *
     * It runs on the migration's own connection, else the selected database, else the default.
     *
     * @param  string  $migration
     * @return void
     */
    public function up($migration)
    {
        $this->runMigration($this->resolve($migration), 'up');
    }

    /**
     * Run a migration method on its connection, like Laravel's migrator: inside a
     * transaction when the migration asks for one and the schema grammar supports it.
     * Migrations without the method are skipped.
     *
     * @param  object  $migration
     */
    protected function runMigration($migration, string $method): void
    {
        if (! method_exists($migration, $method)) {
            return;
        }

        $connection = $this->resolveConnection($migration);

        $callback = fn () => $this->runMethod($connection, $migration, $method);

        if ($this->getSchemaGrammar($connection)->supportsSchemaTransactions()
            && property_exists($migration, 'withinTransaction')
            && $migration->withinTransaction) {
            $connection->transaction($callback);

            return;
        }

        $callback();
    }

    /**
     * Get the schema grammar of the connection.
     *
     * @param  Connection  $connection
     * @return Grammar
     */
    protected function getSchemaGrammar($connection)
    {
        // Connection::getSchemaGrammar() is documented as Grammar, but the grammar stays null
        // until the connection builds a schema builder or sets the default grammar.
        /** @var Grammar|null $grammar */
        $grammar = $connection->getSchemaGrammar();

        if (is_null($grammar)) {
            $connection->useDefaultSchemaGrammar();

            $grammar = $connection->getSchemaGrammar();
        }

        return $grammar;
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param  string  $file
     * @return object
     */
    public function resolve($file)
    {
        $name = implode('_', array_slice(explode('_', $file), 4));

        $class = Str::studly($name);

        $path = $this->getMigrationFilePath($file);

        if (! class_exists($class) && file_exists($path)) {
            return include $path;
        }

        return new $class;
    }

    /**
     * Require in all the migration files in a given path.
     *
     * @param  array<int, string>  $files  The migration names.
     * @return void
     */
    public function requireFiles(array $files)
    {
        $filesystem = $this->laravel->make(Filesystem::class);

        foreach ($files as $file) {
            $filesystem->requireOnce($this->getMigrationFilePath($file));
        }
    }

    /**
     * Get table instance.
     *
     * @return Builder
     */
    public function table()
    {
        return $this->laravel->make(DatabaseManager::class)->connection($this->database ?: null)->table(config('database.migrations.table', 'migrations'));
    }

    /**
     * Find migration data from database by given migration name.
     *
     * @param  string  $migration
     * @return object
     */
    public function find($migration)
    {
        return $this->table()->where('migration', $migration);
    }

    /**
     * Save new migration to database.
     *
     * @param  string  $migration
     * @return mixed
     */
    public function log($migration)
    {
        return $this->table()->insert([
            'migration' => $migration,
            'batch' => $this->getNextBatchNumber(),
        ]);
    }

    /**
     * Get the next migration batch number.
     *
     * @return int
     */
    public function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last migration batch number.
     *
     * @param  array<int, string>|null  $migrations
     * @return int
     */
    public function getLastBatchNumber($migrations = null)
    {
        $table = $this->table();

        if (is_array($migrations)) {
            $table = $table->whereIn('migration', $migrations);
        }

        return $table->max('batch');
    }

    /**
     * Get the last migration batch.
     *
     * @param  array<int, string>  $migrations
     * @return Collection<int, mixed>
     */
    public function getLast($migrations)
    {
        $query = $this->table()
            ->where('batch', $this->getLastBatchNumber($migrations))
            ->whereIn('migration', $migrations);

        $result = $query->orderBy('migration', 'desc')->get();

        return collect($result)->map(fn ($item) => (array) $item)->pluck('migration');
    }

    /**
     * Get the ran migrations.
     *
     * @return Collection<int, mixed>
     */
    public function getRan()
    {
        return $this->table()->pluck('migration');
    }
}
