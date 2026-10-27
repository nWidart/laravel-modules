<?php

namespace Nwidart\Modules\Constants;

class ModuleEvent
{
    /**
     * @deprecated Only fired by the deprecated Module::boot(), which the package no longer calls.
     *             Will be removed in v14.
     */
    const string BOOT = 'boot';

    /**
     * @deprecated Only fired by the deprecated Module::register(), which the package no longer calls.
     *             Will be removed in v14.
     */
    const string REGISTER = 'register';

    const string DISABLING = 'disabling';

    const string DISABLED = 'disabled';

    const string ENABLING = 'enabling';

    const string ENABLED = 'enabled';

    const string CREATING = 'creating';

    const string CREATED = 'created';

    const string DELETING = 'deleting';

    const string DELETED = 'deleted';

    const string USED = 'used';

    const string UNUSED = 'unused';
}
