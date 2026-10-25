<?php

namespace Nwidart\Modules\Migrations;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
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
     * @var Application.
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
     *
     *
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
     * Get migration files.
     *
     * @param  bool  $reverse
     * @return array
     */
    public function getMigrations($reverse = false)
    {
        if (! empty($this->subpath)) {
            $files = $this->laravel['files']->glob($this->getPath().'/'.$this->subpath);
        } else {
            $files = $this->laravel['files']->glob($this->getPath().'/*_*.php');
        }

        // Once we have the array of files in the directory we will just remove the
        // extension and take the basename of the file which is all we need when
        // finding the migrations that haven't been run against the databases.
        if ($files === false) {
            return [];
        }

        $files = array_map(function ($file) {
            return str_replace('.php', '', basename($file));
        }, $files);

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
     * @param  string  $migration
     */
    public function down($migration)
    {
        $this->resolve($migration)->down();
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
     * The connection is the migration's own, else the selected database, else the default.
     * It is made the default connection during the call, so the Schema facade pretends too.
     *
     * @param  object  $migration
     * @return array<int, string>
     */
    protected function getQueries($migration, string $method): array
    {
        $name = method_exists($migration, 'getConnection') ? $migration->getConnection() : null;

        $connection = $this->laravel['db']->connection($name ?: ($this->database ?: null));

        $queries = $connection->pretend(function () use ($connection, $migration, $method) {
            if (method_exists($migration, $method)) {
                $this->runMethod($connection, $migration, $method);
            }
        });

        return array_column($queries, 'query');
    }

    /**
     * Run a migration method with the given connection as the default connection.
     *
     * @param  Connection  $connection
     * @param  object  $migration
     */
    protected function runMethod($connection, $migration, string $method): void
    {
        $db = $this->laravel['db'];

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
     * @param  string  $migration
     */
    public function up($migration)
    {
        $this->resolve($migration)->up();
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

        if (! class_exists($class) && file_exists($this->getPath().'/'.$file.'.php')) {
            return include $this->getPath().'/'.$file.'.php';
        }

        return new $class;
    }

    /**
     * Require in all the migration files in a given path.
     */
    public function requireFiles(array $files)
    {
        $path = $this->getPath();
        foreach ($files as $file) {
            $this->laravel['files']->requireOnce($path.'/'.$file.'.php');
        }
    }

    /**
     * Get table instance.
     *
     * @return Builder
     */
    public function table()
    {
        return $this->laravel['db']->connection($this->database ?: null)->table(config('database.migrations.table', 'migrations'));
    }

    /**
     * Find migration data from database by given migration name.
     *
     * @param  string  $migration
     * @return object
     */
    public function find($migration)
    {
        return $this->table()->whereMigration($migration);
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
     * @param  array|null  $migrations
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
     * @param  array  $migrations
     * @return Collection
     */
    public function getLast($migrations)
    {
        $query = $this->table()
            ->where('batch', $this->getLastBatchNumber($migrations))
            ->whereIn('migration', $migrations);

        $result = $query->orderBy('migration', 'desc')->get();

        return collect($result)->map(function ($item) {
            return (array) $item;
        })->pluck('migration');
    }

    /**
     * Get the ran migrations.
     *
     * @return Collection
     */
    public function getRan()
    {
        return $this->table()->pluck('migration');
    }
}
