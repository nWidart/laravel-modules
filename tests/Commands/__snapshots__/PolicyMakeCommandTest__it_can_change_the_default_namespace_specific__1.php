<?php return '<?php

namespace Modules\\Blog\\SuperPolicies;

use Illuminate\\Auth\\Access\\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
}
';
