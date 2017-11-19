<?php return '<?php

use Illuminate\\Support\\Facades\\Schema;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Database\\Migrations\\Migration;

class DropPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists(\'posts\');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create(\'posts\', function (Blueprint $table) {
            $table->increments(\'id\');

            $table->timestamps();
        });
    }
}
';
