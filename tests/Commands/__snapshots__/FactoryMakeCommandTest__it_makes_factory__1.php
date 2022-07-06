<?php return '<?php
namespace Modules\\Blog\\Database\\factories;

use Illuminate\\Database\\Eloquent\\Factories\\Factory;

class PostFactory extends Factory
{
    /**
     * The name of the factory\'s corresponding model.
     *
     * @var string
     */
    protected $model = \\Modules\\Blog\\Entities\\Post::class;

    /**
     * Define the model\'s default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            //
        ];
    }
}

';
