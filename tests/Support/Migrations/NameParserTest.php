<?php

namespace Nwidart\Modules\Tests\Support\Migrations;

use Nwidart\Modules\Support\Migrations\NameParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NameParserTest extends TestCase
{
    public function test_it_gets_the_original_name()
    {
        $parser = new NameParser('create_users_table');

        self::assertEquals('create_users_table', $parser->getOriginalName());
    }

    public function test_it_gets_the_table_name()
    {
        $parser = new NameParser('create_users_table');

        self::assertEquals('users', $parser->getTableName());
    }

    public function test_it_gets_the_action_name()
    {
        self::assertEquals('create', (new NameParser('create_users_table'))->getAction());
        self::assertEquals('update', (new NameParser('update_users_table'))->getAction());
        self::assertEquals('delete', (new NameParser('delete_users_table'))->getAction());
        self::assertEquals('remove', (new NameParser('remove_users_table'))->getAction());
    }

    public function test_it_gets_first_part_of_name_if_no_action_was_guessed()
    {
        self::assertEquals('something', (new NameParser('something_random'))->getAction());
    }

    public function test_it_gets_the_correct_matched_results()
    {
        $matches = (new NameParser('create_users_table'))->getMatches();

        $expected = [
            'create_users_table',
            'users',
        ];

        self::assertEquals($expected, $matches);
    }

    public function test_it_gets_the_exploded_parts_of_migration_name()
    {
        $parser = new NameParser('create_users_table');

        $expected = [
            'create',
            'users',
            'table',
        ];

        self::assertEquals($expected, $parser->getData());
    }

    public function test_it_can_check_if_current_migration_type_matches_given_type()
    {
        $parser = new NameParser('create_users_table');

        self::assertTrue($parser->is('create'));
    }

    public function test_it_can_check_if_current_migration_is_about_adding()
    {
        self::assertTrue((new NameParser('add_users_table'))->isAdd());
    }

    public function test_it_can_check_if_current_migration_is_about_deleting()
    {
        self::assertTrue((new NameParser('delete_users_table'))->isDelete());
    }

    public function test_it_can_check_if_current_migration_is_about_creating()
    {
        self::assertTrue((new NameParser('create_users_table'))->isCreate());
    }

    public function test_it_can_check_if_current_migration_is_about_dropping()
    {
        self::assertTrue((new NameParser('drop_users_table'))->isDrop());
    }

    public function test_it_makes_a_regex_pattern()
    {
        self::assertEquals('/create_(.*)_table/', (new NameParser('create_users_table'))->getPattern());
        self::assertEquals('/add_(.*)_to_(.*)_table/', (new NameParser('add_column_to_users_table'))->getPattern());
        self::assertEquals('/delete_(.*)_from_(.*)_table/', (new NameParser('delete_users_table'))->getPattern());
    }

    #[DataProvider('tableNameProvider')]
    public function test_it_gets_the_table_name_for_every_action(string $name, ?string $table)
    {
        self::assertSame($table, (new NameParser($name))->getTableName());
    }

    public static function tableNameProvider(): array
    {
        return [
            // Names that already worked keep their table name.
            ['create_users_table', 'users'],
            ['create_user_roles_table', 'user_roles'],
            ['make_users_table', 'users'],
            ['add_votes_to_users_table', 'users'],
            ['add_votes_to_user_roles_table', 'user_roles'],
            ['append_votes_to_users_table', 'users'],
            ['update_votes_to_users_table', 'users'],
            ['insert_votes_to_users_table', 'users'],
            ['delete_votes_from_users_table', 'users'],
            ['remove_votes_from_users_table', 'users'],
            ['alter_votes_from_users_table', 'users'],
            ['drop_users_table', 'users'],
            ['destroy_users_table', 'users'],
            // Names without a preposition.
            ['alter_users_table', 'users'],
            ['update_users_table', 'users'],
            ['delete_users_table', 'users'],
            ['remove_users_table', 'users'],
            // Names without a "_table" suffix.
            ['create_users', 'users'],
            ['make_user_roles', 'user_roles'],
            ['add_votes_to_users', 'users'],
            ['update_users', 'users'],
            ['remove_votes_from_users', 'users'],
            ['delete_votes_from_user_roles', 'user_roles'],
            ['alter_users', 'users'],
            ['drop_users', 'users'],
            ['destroy_users', 'users'],
            // Names without a known action.
            ['something_random_name', null],
        ];
    }
}
