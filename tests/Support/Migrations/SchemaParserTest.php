<?php

namespace Nwidart\Modules\Tests\Support\Migrations;

use Nwidart\Modules\Support\Migrations\SchemaParser;
use PHPUnit\Framework\TestCase;

class SchemaParserTest extends TestCase
{
    public function test_it_generates_migration_method_calls()
    {
        $parser = new SchemaParser('username:string, password:integer');

        $expected = <<<TEXT
\t\t\t\$table->string('username');
\t\t\t\$table->integer('password');\n
TEXT;

        self::assertEquals($expected, $parser->render());
    }

    public function test_it_generates_migration_methods_for_up_method()
    {
        $parser = new SchemaParser('username:string, password:integer');

        $expected = <<<TEXT
\t\t\t\$table->string('username');
\t\t\t\$table->integer('password');\n
TEXT;

        self::assertEquals($expected, $parser->up());
    }

    public function test_it_generates_migration_methods_for_down_method()
    {
        $parser = new SchemaParser('username:string, password:integer');

        $expected = <<<TEXT
\t\t\t\$table->dropColumn('username');
\t\t\t\$table->dropColumn('password');\n
TEXT;

        self::assertEquals($expected, $parser->down());
    }
}
