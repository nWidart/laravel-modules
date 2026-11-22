<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\BlogController;

Route::resource('blogs', BlogController::class)->names('blog');
