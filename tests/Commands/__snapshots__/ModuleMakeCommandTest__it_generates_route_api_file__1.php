<?php return '<?php

Route::group([\'prefix\' => \'blog/api\'], function()
{
    Route::get(\'/\', \'BlogController@index\');
});
';
