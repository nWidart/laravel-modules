<?php namespace Modules\Recipe\Repositories\Eloquent;

use Modules\Recipe\Events\RecipeWasCreated;
use Modules\Recipe\Repositories\RecipeRepository;
use Modules\Core\Repositories\Eloquent\EloquentBaseRepository;

class EloquentRecipeRepository extends EloquentBaseRepository implements RecipeRepository
{
    public function create($data)
    {
        $recipe = $this->model->create($data);

        event(new RecipeWasCreated($recipe, $data));

        return $recipe;
    }
}
