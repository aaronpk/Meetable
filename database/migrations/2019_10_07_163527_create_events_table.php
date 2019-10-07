<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->string('key', 30);
            $table->string('slug', 255)->nullable();
            $table->string('name', 255);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('timezone', 100)->nullable();
            $table->string('location_name', 255);
            $table->string('location_address', 255);
            $table->string('location_locality', 255);
            $table->string('location_region', 255);
            $table->string('location_country', 255);
            $table->string('website', 512)->nullable();
            $table->string('tickets_url', 512)->nullable();
            $table->text('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events');
    }
}
