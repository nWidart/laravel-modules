<?php return 'const { mix } = require(\'laravel-mix\');

mix.setPublicPath(\'public\')
    .copy(\'public\', __dirname + \'/../../public/modules/blog\')

mix.js(__dirname + \'/Resources/assets/js/app.js\', \'public/js\')
    .sass( __dirname + \'/Resources/assets/sass/app.scss\', \'public/css\');';
