<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EventSummary extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->longtext('summary')->nullable();
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->longtext('summary')->nullable();
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
            $table->dropColumn('summary');
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('summary');
        });
    }
}
