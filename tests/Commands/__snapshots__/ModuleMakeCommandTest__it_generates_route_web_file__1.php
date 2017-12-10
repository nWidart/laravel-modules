<?php return '<?php

Route::group([\'prefix\' => \'blog\'], function()
{
    Route::get(\'/\', \'BlogController@index\');
});
';
