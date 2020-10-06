<?php

namespace Nwidart\Modules\Providers;

use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{

    protected $consoleNamespace = "Nwidart\\Modules\\Commands";
    /**
     * The available commands
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Register the commands.
     */
    public function register()
    {
        foreach (config('commands') as $command) {
            $this->commands[] = trim($this->consoleNamespace . "\\" . $command);
        }

        $this->commands($this->commands);
    }

    /**
     * @return array
     */
    public function provides()
    {
        $provides = $this->commands;

        return $provides;
    }
}
