<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UnlistedEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('unlisted')->default(0);
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->boolean('unlisted')->default(0);
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
            $table->dropColumn('unlisted');
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('unlisted');
        });
    }
}
