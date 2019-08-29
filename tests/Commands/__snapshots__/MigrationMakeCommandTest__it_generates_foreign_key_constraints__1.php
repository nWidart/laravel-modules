<?php return '<?php

use Illuminate\\Support\\Facades\\Schema;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Database\\Migrations\\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(\'posts\', function (Blueprint $table) {
            $table->bigIncrements(\'id\');
			$table->integer(\'user_id\')->unsigned();
			$table->foreign(\'user_id\')->references(\'id\')->on(\'users\');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(\'posts\');
    }
}
';
