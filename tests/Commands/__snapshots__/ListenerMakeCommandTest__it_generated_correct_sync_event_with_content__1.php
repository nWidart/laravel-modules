<?php return '<?php

namespace Modules\\Blog\\Listeners;

use Modules\\Blog\\Events\\UserWasCreated;
use Illuminate\\Queue\\InteractsWithQueue;
use Illuminate\\Contracts\\Queue\\ShouldQueue;

class NotifyUsersOfANewPost
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param UserWasCreated $event
     * @return void
     */
    public function handle(UserWasCreated $event)
    {
        //
    }
}
';
