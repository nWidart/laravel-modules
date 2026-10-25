<?php

namespace Nwidart\Modules\Constants;

class ModuleEvent
{
    /**
     * @deprecated Only fired by the deprecated Module::boot(), which the package no longer calls.
     *             Will be removed in v14.
     */
    const BOOT = 'boot';

    /**
     * @deprecated Only fired by the deprecated Module::register(), which the package no longer calls.
     *             Will be removed in v14.
     */
    const REGISTER = 'register';

    const DISABLING = 'disabling';

    const DISABLED = 'disabled';

    const ENABLING = 'enabling';

    const ENABLED = 'enabled';

    const CREATING = 'creating';

    const CREATED = 'created';

    const DELETING = 'deleting';

    const DELETED = 'deleted';

    const USED = 'used';

    const UNUSED = 'unused';
}
