<?php

namespace Nwidart\Modules\Tests\Support\Fixtures;

use Nwidart\Modules\Support\ReplacementKeyCommand;

/**
 * Returns a fixed value, to tell a class replacement apart from a built-in one.
 */
class StaticValueReplacement extends ReplacementKeyCommand
{
    public const VALUE = 'from-replacement-class';

    public function handle(): string
    {
        return self::VALUE;
    }
}
