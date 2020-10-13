<?php return '<?php

namespace Modules\\Blog\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \\Modules\\Blog\\Database\\Factories\\PostFactory::new();
    }

    protected $fillable = [];
}
';
