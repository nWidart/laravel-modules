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

    public function test_it_keeps_every_relation_when_belongs_to_is_given_twice()
    {
        $parser = new SchemaParser('belongsTo:user:id:users, belongsTo:category:id:categories');

        $expected = <<<TEXT
\t\t\t\$table->integer('user_id')->unsigned();
\t\t\t\$table->foreign('user_id')->references('id')->on('users');
\t\t\t\$table->integer('category_id')->unsigned();
\t\t\t\$table->foreign('category_id')->references('id')->on('categories');\n
TEXT;

        self::assertEquals($expected, $parser->up());
        self::assertCount(2, $parser->getFields());
    }

    public function test_it_drops_the_foreign_key_and_column_of_a_relation_in_down_method()
    {
        $parser = new SchemaParser('belongsTo:user:id:users:onDelete(\'cascade\'), belongsTo:category:id:categories');

        $expected = <<<TEXT
\t\t\t\$table->dropForeign(['user_id']);
\t\t\t\$table->dropColumn('user_id');
\t\t\t\$table->dropForeign(['category_id']);
\t\t\t\$table->dropColumn('category_id');\n
TEXT;

        self::assertEquals($expected, $parser->down());
    }

    public function test_it_uses_drop_methods_for_custom_attributes_in_down_method()
    {
        $parser = new SchemaParser('soft_delete, remember_token');

        $up = <<<TEXT
\t\t\t\$table->softDeletes();
\t\t\t\$table->rememberToken();\n
TEXT;

        $down = <<<TEXT
\t\t\t\$table->dropSoftDeletes();
\t\t\t\$table->dropRememberToken();\n
TEXT;

        self::assertEquals($up, $parser->up());
        self::assertEquals($down, $parser->down());
    }

    public function test_it_keeps_whitespace_inside_quoted_strings()
    {
        $parser = new SchemaParser("title:string:default('Hello World'), body:text:comment(\"A long body\")");

        $expected = <<<TEXT
\t\t\t\$table->string('title')->default('Hello World');
\t\t\t\$table->text('body')->comment("A long body");\n
TEXT;

        self::assertEquals($expected, $parser->up());
    }

    public function test_it_still_removes_whitespace_outside_quoted_strings()
    {
        $parser = new SchemaParser(' title : string : nullable ,  body:text ');

        $expected = <<<TEXT
\t\t\t\$table->string('title')->nullable();
\t\t\t\$table->text('body');\n
TEXT;

        self::assertEquals($expected, $parser->up());
    }

    public function test_it_does_not_split_on_commas_inside_parentheses_or_quotes()
    {
        $parser = new SchemaParser("votes:integer:default(1,5), name:string:default('a,b'), price:float:default(1.5)");

        $expected = <<<TEXT
\t\t\t\$table->integer('votes')->default(1,5);
\t\t\t\$table->string('name')->default('a,b');
\t\t\t\$table->float('price')->default(1.5);\n
TEXT;

        self::assertEquals($expected, $parser->up());
    }

    public function test_it_does_not_split_on_colons_inside_parentheses_or_quotes()
    {
        $parser = new SchemaParser("starts_at:time:default('12:00'), total:integer:default(DB::raw('0'))");

        $expected = <<<TEXT
\t\t\t\$table->time('starts_at')->default('12:00');
\t\t\t\$table->integer('total')->default(DB::raw('0'));\n
TEXT;

        self::assertEquals($expected, $parser->up());
    }

    public function test_it_passes_column_type_arguments_after_the_column_name()
    {
        $parser = new SchemaParser('amount:decimal(8, 2):default(0), code:string(10):unique, flag:boolean()');

        $expected = <<<TEXT
\t\t\t\$table->decimal('amount', 8, 2)->default(0);
\t\t\t\$table->string('code', 10)->unique();
\t\t\t\$table->boolean('flag');\n
TEXT;

        $down = <<<TEXT
\t\t\t\$table->dropColumn('amount');
\t\t\t\$table->dropColumn('code');
\t\t\t\$table->dropColumn('flag');\n
TEXT;

        self::assertEquals($expected, $parser->up());
        self::assertEquals($down, $parser->down());
    }

    public function test_it_skips_empty_fields()
    {
        $parser = new SchemaParser('username:string,, password:integer,');

        $expected = <<<TEXT
\t\t\t\$table->string('username');
\t\t\t\$table->integer('password');\n
TEXT;

        self::assertEquals($expected, $parser->up());
        self::assertSame('', (new SchemaParser(''))->up());
        self::assertSame('', (new SchemaParser)->up());
    }

    public function test_it_parses_to_an_array_keyed_by_column()
    {
        $parser = new SchemaParser("username:string:default('John Doe'), age:integer:unsigned");

        self::assertSame([
            'username' => ['string', "default('John Doe')"],
            'age' => ['integer', 'unsigned'],
        ], $parser->toArray());
    }
}
