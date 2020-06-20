<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EventStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('status', 50)->default('confirmed')->after('sort_date');
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->string('status', 50)->default('confirmed')->after('sort_date');
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
            $table->dropColumn('status');
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
