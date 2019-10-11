<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('modules.activators.database.table'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('alias')->nullable();
            $table->string('path');
            $table->text('description')->nullable();
            $table->text('keywords')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sequence')->default(0);
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
        Schema::dropIfExists(config('modules.activators.database.table'));
    }
}
