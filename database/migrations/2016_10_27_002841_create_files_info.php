<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFilesInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('filesInfo', function (Blueprint $table) {
            $table->increments('id');
            $table->string('filename');
            $table->string('basename');
            $table->string('path');
            $table->string('username');
            $table->string('pid');
            $table->string('type');
            $table->string('size');
            $table->string('status');
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
        Schema::drop('filesInfo');
    }
}
