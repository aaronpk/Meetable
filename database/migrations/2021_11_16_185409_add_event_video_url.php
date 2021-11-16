<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEventVideoUrl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('video_url', 255)->default('');
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->string('video_url', 255)->default('');
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
            $table->dropColumn('video_url');
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('video_url');
        });
    }
}
