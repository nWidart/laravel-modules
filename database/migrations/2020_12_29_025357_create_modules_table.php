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
        Schema::create('modules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('path');
            $table->string('alias')->nullable();
            $table->string('version')->default('1.0.0');
            $table->text('description')->nullable();
            $table->text('keywords')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->text('providers')->nullable();
            $table->text('aliases')->nullable();
            $table->text('files')->nullable();
            $table->text('requires')->nullable();
            $table->integer('priority')->default(0);
            $table->nullableTimestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('modules');
    }
}
