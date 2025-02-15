<?php

namespace Nwidart\Modules;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection
{
    /**
     * Get items collections.
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get the collection of items as a plain array.
     */
    public function toArray(): array
    {
        return array_map(function ($value) {
            if ($value instanceof Module) {
                $attributes = $value->json()->getAttributes();
                $attributes['path'] = $value->getPath();

                return $attributes;
            }

            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, $this->items);
    }
}
