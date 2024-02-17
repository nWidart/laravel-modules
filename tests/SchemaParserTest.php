<?php

use Nwidart\Modules\Support\Migrations\SchemaParser;


it('generates migration method calls', function () {
    $parser = new SchemaParser('username:string, password:integer');

    $expected = <<<TEXT
\t\t\t\$table->string('username');
\t\t\t\$table->integer('password');\n
TEXT;

    self::assertEquals($expected, $parser->render());
});

it('generates migration methods for up method', function () {
    $parser = new SchemaParser('username:string, password:integer');

    $expected = <<<TEXT
\t\t\t\$table->string('username');
\t\t\t\$table->integer('password');\n
TEXT;

    self::assertEquals($expected, $parser->up());
});

it('generates migration methods for down method', function () {
    $parser = new SchemaParser('username:string, password:integer');

    $expected = <<<TEXT
\t\t\t\$table->dropColumn('username');
\t\t\t\$table->dropColumn('password');\n
TEXT;

    self::assertEquals($expected, $parser->down());
});
