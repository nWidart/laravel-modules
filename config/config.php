<?php

use Nwidart\Modules\Activators\FileActivator;
use Nwidart\Modules\Providers\ConsoleServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Module Namespace
    |--------------------------------------------------------------------------
    |
    | Default module namespace.
    |
    */

    'namespace' => 'Modules',

    /*
    |--------------------------------------------------------------------------
    | Module Stubs
    |--------------------------------------------------------------------------
    |
    | Default module stubs.
    |
    */

    'stubs' => [
        'enabled' => false,
        'path' => base_path('vendor/nwidart/laravel-modules/src/Commands/stubs'),
        'files' => [
            'routes/web' => 'routes/web.php',
            'routes/api' => 'routes/api.php',
            'views/index' => 'resources/views/index.blade.php',
            'views/master' => 'resources/views/layouts/master.blade.php',
            'scaffold/config' => 'config/config.php',
            'composer' => 'composer.json',
            'assets/js/app' => 'resources/assets/js/app.js',
            'assets/sass/app' => 'resources/assets/sass/app.scss',
            'vite' => 'vite.config.js',
            'package' => 'package.json',
        ],
        'replacements' => [
            'routes/web' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE', 'CONTROLLER_NAMESPACE'],
            'routes/api' => ['LOWER_NAME', 'STUDLY_NAME'],
            'vite' => ['LOWER_NAME'],
            'json' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE', 'PROVIDER_NAMESPACE'],
            'views/index' => ['LOWER_NAME'],
            'views/master' => ['LOWER_NAME', 'STUDLY_NAME'],
            'scaffold/config' => ['STUDLY_NAME'],
            'composer' => [
                'LOWER_NAME',
                'STUDLY_NAME',
                'VENDOR',
                'AUTHOR_NAME',
                'AUTHOR_EMAIL',
                'MODULE_NAMESPACE',
                'PROVIDER_NAMESPACE',
            ],
        ],
        'gitkeep' => true,
    ],
    'paths' => [
        /*
        |--------------------------------------------------------------------------
        | Modules path
        |--------------------------------------------------------------------------
        |
        | This path is used to save the generated module.
        | This path will also be added automatically to the list of scanned folders.
        |
        */

        'modules' => base_path('Modules'),
        /*
        |--------------------------------------------------------------------------
        | Modules assets path
        |--------------------------------------------------------------------------
        |
        | Here you may update the modules' assets path.
        |
        */

        'assets' => public_path('modules'),
        /*
        |--------------------------------------------------------------------------
        | The migrations' path
        |--------------------------------------------------------------------------
        |
        | Where you run the 'module:publish-migration' command, where do you publish the
        | the migration files?
        |
        */

        'migration' => base_path('database/migrations'),
        /*
        |--------------------------------------------------------------------------
        | Generator path
        |--------------------------------------------------------------------------
        | Customise the paths where the folders will be generated.
        | Set the generate's key to false to not generate that folder
        */
        'generator' => [
            'config' => ['path' => 'config', 'generate' => true],
            'command' => ['path' => 'app/Console', 'generate' => false],
            'channels' => ['path' => 'app/Broadcasting', 'generate' => false],
            'migration' => ['path' => 'database/migrations', 'generate' => true],
            'seeder' => [
                'path' => 'database/seeders',
                'namespace' => 'database/seeders',
                'generate' => true,
            ],
            'factory' => ['path' => 'database/factories', 'generate' => true],
            'model' => ['path' => 'app/Models', 'generate' => true],
            'observer' => ['path' => 'app/Observers', 'generate' => false],
            'routes' => ['path' => 'routes', 'generate' => true],
            'controller' => ['path' => 'app/Http/Controllers', 'generate' => true],
            'filter' => ['path' => 'app/Http/Middleware', 'generate' => true],
            'request' => ['path' => 'app/Http/Requests', 'generate' => true],
            'provider' => ['path' => 'app/Providers', 'generate' => true],
            'assets' => ['path' => 'resources/assets', 'generate' => true],
            'lang' => ['path' => 'lang', 'generate' => true],
            'views' => ['path' => 'resources/views', 'generate' => true],
            'test' => ['path' => 'tests/Unit', 'generate' => true],
            'test-feature' => ['path' => 'tests/Feature', 'generate' => true],
            'repository' => ['path' => 'app/Repositories', 'generate' => false],
            'event' => ['path' => 'app/Events', 'generate' => false],
            'listener' => ['path' => 'app/Listeners', 'generate' => false],
            'policies' => ['path' => 'app/Policies', 'generate' => false],
            'rules' => ['path' => 'app/Rules', 'generate' => false],
            'jobs' => ['path' => 'app/Jobs', 'generate' => false],
            'emails' => ['path' => 'app/Emails', 'generate' => false],
            'notifications' => ['path' => 'app/Notifications', 'generate' => false],
            'resource' => ['path' => 'app/Resources', 'generate' => false],
            'component-view' => ['path' => 'resources/views/components', 'generate' => false],
            'component-class' => ['path' => 'app/View/Components', 'generate' => false],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Package commands
    |--------------------------------------------------------------------------
    |
    | Here you can define which commands will be visible and used in your
    | application. If for example, you don't use some of the commands provided
    | you can add them to except section. and can add own command to merge section.
    |
    */
    'commands' => ConsoleServiceProvider::defaultCommands()
        ->merge([
            // New commands go here
        ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Scan Path
    |--------------------------------------------------------------------------
    |
    | Here you define which folder will be scanned. By default will scan vendor
    | directory. This is useful if you host the package in packagist website.
    |
    */

    'scan' => [
        'enabled' => false,
        'paths' => [
            base_path('vendor/*/*'),
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Composer File Template
    |--------------------------------------------------------------------------
    |
    | Here is the config for the composer.json file, generated by this package
    |
    */

    'composer' => [
        'vendor' => 'nwidart',
        'author' => [
            'name' => 'Nicolas Widart',
            'email' => 'n.widart@gmail.com',
        ],
        'composer-output' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Here is the config for setting up the caching feature.
    |
    */
    'cache' => [
        'enabled' => false,
        'driver' => 'file',
        'key' => 'laravel-modules',
        'lifetime' => 60,
    ],
    /*
    |--------------------------------------------------------------------------
    | Choose what laravel-modules will register as custom namespaces.
    | Setting one to false will require you to register that part
    | in your own Service Provider class.
    |--------------------------------------------------------------------------
    */
    'register' => [
        'translations' => true,
        /**
         * load files on boot or register method
         *
         * Note: boot not compatible with asgardcms
         *
         * @example boot|register
         */
        'files' => 'register',
    ],

    /*
    |--------------------------------------------------------------------------
    | Activators
    |--------------------------------------------------------------------------
    |
    | You can define new types of activators here, file, database, etc. The only
    | required parameter is 'class'.
    | The file activator will store the activation status in storage/installed_modules
    */
    'activators' => [
        'file' => [
            'class' => FileActivator::class,
            'statuses-file' => base_path('modules_statuses.json'),
            'cache-key' => 'activator.installed',
            'cache-lifetime' => 604800,
        ],
    ],

    'activator' => 'file',
];
