<?php

use Nwidart\Modules\Support\Migrations\NameParser;

it('gets the original name', function () {
    $parser = new NameParser('create_users_table');

    self::assertEquals('create_users_table', $parser->getOriginalName());
});

it('gets the table name', function () {
    $parser = new NameParser('create_users_table');

    self::assertEquals('users', $parser->getTableName());
});

it('gets the action name', function () {
    self::assertEquals('create', (new NameParser('create_users_table'))->getAction());
    self::assertEquals('update', (new NameParser('update_users_table'))->getAction());
    self::assertEquals('delete', (new NameParser('delete_users_table'))->getAction());
    self::assertEquals('remove', (new NameParser('remove_users_table'))->getAction());
});

it('gets first part of name if no action was guessed', function () {
    self::assertEquals('something', (new NameParser('something_random'))->getAction());
});

it('gets the correct matched results', function () {
    $matches = (new NameParser('create_users_table'))->getMatches();

    $expected = [
        'create_users_table',
        'users',
    ];

    self::assertEquals($expected, $matches);
});

it('gets the exploded parts of migration name', function () {
    $parser = new NameParser('create_users_table');

    $expected = [
        'create',
        'users',
        'table',
    ];

    self::assertEquals($expected, $parser->getData());
});

it('can check if current migration type matches given type', function () {
    $parser = new NameParser('create_users_table');

    self::assertTrue($parser->is('create'));
});

it('can check if current migration is about adding', function () {
    self::assertTrue((new NameParser('add_users_table'))->isAdd());
});

it('can check if current migration is about deleting', function () {
    self::assertTrue((new NameParser('delete_users_table'))->isDelete());
});

it('can check if current migration is about creating', function () {
    self::assertTrue((new NameParser('create_users_table'))->isCreate());
});

it('can check if current migration is about dropping', function () {
    self::assertTrue((new NameParser('drop_users_table'))->isDrop());
});

it('makes a regex pattern', function () {
    self::assertEquals('/create_(.*)_table/', (new NameParser('create_users_table'))->getPattern());
    self::assertEquals('/add_(.*)_to_(.*)_table/', (new NameParser('add_column_to_users_table'))->getPattern());
    self::assertEquals('/delete_(.*)_from_(.*)_table/', (new NameParser('delete_users_table'))->getPattern());
});
