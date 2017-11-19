<?php

namespace Modules\Recipe\Repositories\Cache;

use Modules\Core\Repositories\Cache\BaseCacheDecorator;
use Modules\Recipe\Repositories\RecipeRepository;

class CacheRecipeDecorator extends BaseCacheDecorator implements RecipeRepository
{
    public function __construct(RecipeRepository $recipe)
    {
        parent::__construct();
        $this->entityName = 'recipe.recipes';
        $this->repository = $recipe;
    }
}
