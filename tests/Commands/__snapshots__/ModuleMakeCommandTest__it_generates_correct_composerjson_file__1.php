<?php return '{
    "name": "nwidart/blog",
    "description": "",
    "authors": [
        {
            "name": "Nicolas Widart",
            "email": "n.widart@gmail.com"
        }
    ],
    "extra": {
        "laravel": {
            "providers": [
                "Modules\\\Blog\\\Providers\\\BlogServiceProvider"
            ],
            "aliases": {
                
            }
        }
    },
    "autoload": {
        "psr-4": {
            "Modules\\\\Blog\\\\": ""
        }
    }
}
';
