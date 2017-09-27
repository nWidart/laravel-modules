<?php return '<?php

namespace Modules\\Blog\\Database\\Seeders;

use Illuminate\\Database\\Seeder;
use Illuminate\\Database\\Eloquent\\Model;

class BlogDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // $this->call("OthersTableSeeder");
    }
}
';
