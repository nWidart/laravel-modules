<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\App\Http\Controllers\BlogController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('blog', BlogController::class)->names('blog');
});
