<?php

namespace Modules\Recipe\Events;

use Illuminate\Database\Eloquent\Model;
use Modules\Media\Contracts\StoringMedia;

class RecipeWasCreated implements StoringMedia
{
    private $recipe;

    private $data;

    public function __construct($recipe, $data)
    {
        $this->recipe = $recipe;
        $this->data = $data;
    }

    /**
     * Return the entity
     *
     * @return Model
     */
    public function getEntity()
    {
        return $this->recipe;
    }

    /**
     * Return the ALL data sent
     *
     * @return array
     */
    public function getSubmissionData()
    {
        return $this->data;
    }
}
