<?php

namespace Nwidart\Modules\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string name
 * @property string path
 * @property string alias
 * @property string description
 * @property string keywords
 * @property boolean is_active
 * @property int order
 * @property string providers
 * @property string aliases
 * @property string files
 * @property string requires
 */
class ModuleEntity extends Model
{
    public $table = 'modules';
}
