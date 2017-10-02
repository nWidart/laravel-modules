<?php return '<?php

Route::group([\'middleware\' => \'web\', \'prefix\' => \'blog\', \'namespace\' => \'Modules\\Blog\\Http\\Controllers\'], function()
{
    Route::get(\'/\', \'BlogController@index\');
});
';
