<?php

namespace Nwidart\Modules\Tests\Migrations\Fixtures;

use Illuminate\Database\Schema\Grammars\SQLiteGrammar;

/**
 * SQLite runs schema changes inside transactions, but Laravel's SQLite grammar
 * reports no support for them. This grammar opts in, so tests can cover the
 * transactional migration path on the in-memory database.
 */
class TransactionalSQLiteGrammar extends SQLiteGrammar
{
    protected $transactions = true;
}
