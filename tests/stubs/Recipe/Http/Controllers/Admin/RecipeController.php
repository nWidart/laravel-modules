<?php

namespace Modules\Recipe\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Laracasts\Flash\Flash;
use Modules\Core\Http\Controllers\Admin\AdminBaseController;
use Modules\Media\Repositories\FileRepository;
use Modules\Recipe\Entities\Recipe;
use Modules\Recipe\Repositories\RecipeRepository;

class RecipeController extends AdminBaseController
{
    /**
     * @var RecipeRepository
     */
    private $recipe;
    /**
     * @var FileRepository
     */
    private $file;

    public function __construct(RecipeRepository $recipe, FileRepository $file)
    {
        parent::__construct();

        $this->recipe = $recipe;
        $this->file = $file;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $recipes = $this->recipe->all();

        return view('recipe::admin.recipes.index', compact('recipes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('recipe::admin.recipes.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $this->recipe->create($request->all());

        flash()->success(trans('core::core.messages.resource created', ['name' => trans('recipe::recipes.title.recipes')]));

        return redirect()->route('admin.recipe.recipe.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Recipe $recipe
     * @return Response
     */
    public function edit(Recipe $recipe)
    {
        $galleryFiles = $this->file->findMultipleFilesByZoneForEntity('gallery', $recipe);
        $featured_image = $this->file->findFileByZoneForEntity('featured_image', $recipe);

        return view('recipe::admin.recipes.edit', compact('recipe', 'galleryFiles', 'featured_image'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Recipe $recipe
     * @param  Request $request
     * @return Response
     */
    public function update(Recipe $recipe, Request $request)
    {
        $this->recipe->update($recipe, $request->all());

        flash()->success(trans('core::core.messages.resource updated', ['name' => trans('recipe::recipes.title.recipes')]));

        return redirect()->route('admin.recipe.recipe.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Recipe $recipe
     * @return Response
     */
    public function destroy(Recipe $recipe)
    {
        $this->recipe->destroy($recipe);

        flash()->success(trans('core::core.messages.resource deleted', ['name' => trans('recipe::recipes.title.recipes')]));

        return redirect()->route('admin.recipe.recipe.index');
    }
}
