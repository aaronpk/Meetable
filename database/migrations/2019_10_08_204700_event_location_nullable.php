<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EventLocationNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('location_name', 255)->nullable()->change();
            $table->string('location_address', 255)->nullable()->change();
            $table->string('location_locality', 255)->nullable()->change();
            $table->string('location_region', 255)->nullable()->change();
            $table->string('location_country', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('location_name', 255)->change();
            $table->string('location_address', 255)->change();
            $table->string('location_locality', 255)->change();
            $table->string('location_region', 255)->change();
            $table->string('location_country', 255)->change();
        });
    }
}
