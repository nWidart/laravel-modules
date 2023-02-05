<?php

namespace Nwidart\Modules\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string name
 * @property string path
 * @property string alias
 * @property string version
 * @property string description
 * @property string keywords
 * @property bool   is_active
 * @property int    order
 * @property string providers
 * @property string aliases
 * @property string files
 * @property string requires
 * @property string priority
 * @mixin Builder
 */
class ModuleEntity extends Model
{
    public $table = 'modules';

    protected $fillable = [
        'name',
        'path',
        'alias',
        'version',
        'description',
        'keywords',
        'is_active',
        'order',
        'providers',
        'aliases',
        'files',
        'requires',
        'priority',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'keywords'  => 'array',
        'providers' => 'array',
        'requires'  => 'array',
        'aliases'   => 'object',
        'files'     => 'array',
    ];
}
