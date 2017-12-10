<?php return '<?php

/*
|--------------------------------------------------------------------------
| Register Namespaces And Routes
|--------------------------------------------------------------------------
|
| When a module starting, this file will executed automatically. This helps
| to register some namespaces like translator or view. Also this file
| will load the routes file for each module. You may also modify
| this file as you want.
|
*/

if (!app()->routesAreCached()) {

    $namespace = \'Modules\Blog\Http\Controllers\';

    Route::prefix(\'blog\')
        ->middleware(\'web\')
        ->namespace($namespace)
        ->group(module_path(\'blog\') . \'/routes/web.php\');

    Route::prefix(\'blog/api\')
        ->middleware(\'api\')
        ->namespace($namespace)
        ->group(module_path(\'blog\') . \'/routes/api.php\');
}
';
