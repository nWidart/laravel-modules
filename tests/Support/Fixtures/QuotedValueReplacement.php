<?php

namespace Nwidart\Modules\Tests\Support\Fixtures;

use Nwidart\Modules\Support\ReplacementKeyCommand;

/**
 * Returns a value that is not JSON-escaped, to exercise the invalid JSON warning.
 */
class QuotedValueReplacement extends ReplacementKeyCommand
{
    public function handle(): string
    {
        return 'Acme\Pkg "q"';
    }
}
