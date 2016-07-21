<?php

use Illuminate\Routing\Router;

/** @var Router $router */
$router->group(['prefix' =>'/recipe'], function (Router $router) {
        $router->bind('recipes', function ($id) {
            return app('Modules\Recipe\Repositories\RecipeRepository')->find($id);
        });
        $router->resource('recipes', 'RecipeController', ['except' => ['show'], 'names' => [
            'index' => 'admin.recipe.recipe.index',
            'create' => 'admin.recipe.recipe.create',
            'store' => 'admin.recipe.recipe.store',
            'edit' => 'admin.recipe.recipe.edit',
            'update' => 'admin.recipe.recipe.update',
            'destroy' => 'admin.recipe.recipe.destroy',
        ]]);
// append

});
