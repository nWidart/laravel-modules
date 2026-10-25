<?php

namespace Nwidart\Modules\Exceptions;

/** @phpstan-consistent-constructor */
class InvalidAssetPath extends \Exception
{
    /**
     * @param  string  $asset
     * @return static
     */
    public static function missingModuleName($asset)
    {
        return new static("Module name was not specified in asset [$asset].");
    }
}
