<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RsvpsEnabled extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('rsvps_enabled')->default(1);
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->boolean('rsvps_enabled')->default(1);
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
            $table->dropColumn('rsvps_enabled');
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('rsvps_enabled');
        });
    }
}
