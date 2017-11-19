<?php

namespace Nwidart\Modules\Tests;

use Nwidart\Modules\Support\Migrations\NameParser;

class NameParserTest extends \PHPUnit\Framework\TestCase
{
    /** @test */
    public function it_gets_the_original_name()
    {
        $parser = new NameParser('create_users_table');

        self::assertEquals('create_users_table', $parser->getOriginalName());
    }

    /** @test */
    public function it_gets_the_table_name()
    {
        $parser = new NameParser('create_users_table');

        self::assertEquals('users', $parser->getTableName());
    }

    /** @test */
    public function it_gets_the_action_name()
    {
        self::assertEquals('create', (new NameParser('create_users_table'))->getAction());
        self::assertEquals('update', (new NameParser('update_users_table'))->getAction());
        self::assertEquals('delete', (new NameParser('delete_users_table'))->getAction());
        self::assertEquals('remove', (new NameParser('remove_users_table'))->getAction());
    }

    /** @test */
    public function it_gets_first_part_of_name_if_no_action_was_guessed()
    {
        self::assertEquals('something', (new NameParser('something_random'))->getAction());
    }

    /** @test */
    public function it_gets_the_correct_matched_results()
    {
        $matches = (new NameParser('create_users_table'))->getMatches();

        $expected = [
            'create_users_table',
            'users',
        ];

        self::assertEquals($expected, $matches);
    }

    /** @test */
    public function it_gets_the_exploded_parts_of_migration_name()
    {
        $parser = new NameParser('create_users_table');

        $expected = [
            'create',
            'users',
            'table',
        ];

        self::assertEquals($expected, $parser->getData());
    }

    /** @test */
    public function it_can_check_if_current_migration_type_matches_given_type()
    {
        $parser = new NameParser('create_users_table');

        self::assertTrue($parser->is('create'));
    }

    /** @test */
    public function it_can_check_if_current_migration_is_about_adding()
    {
        self::assertTrue((new NameParser('add_users_table'))->isAdd());
    }

    /** @test */
    public function it_can_check_if_current_migration_is_about_deleting()
    {
        self::assertTrue((new NameParser('delete_users_table'))->isDelete());
    }

    /** @test */
    public function it_can_check_if_current_migration_is_about_creating()
    {
        self::assertTrue((new NameParser('create_users_table'))->isCreate());
    }

    /** @test */
    public function it_can_check_if_current_migration_is_about_dropping()
    {
        self::assertTrue((new NameParser('drop_users_table'))->isDrop());
    }

    /** @test */
    public function it_makes_a_regex_pattern()
    {
        self::assertEquals('/create_(.*)_table/', (new NameParser('create_users_table'))->getPattern());
        self::assertEquals('/add_(.*)_to_(.*)_table/', (new NameParser('add_column_to_users_table'))->getPattern());
        self::assertEquals('/delete_(.*)_from_(.*)_table/', (new NameParser('delete_users_table'))->getPattern());
    }
}
