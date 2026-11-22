<?php

namespace Nwidart\Modules\Tests\Support\Fixtures\Blog\View\Components;

use Illuminate\View\Component;

class LifecycleBadge extends Component
{
    public function render(): string
    {
        return 'Module badge';
    }
}
