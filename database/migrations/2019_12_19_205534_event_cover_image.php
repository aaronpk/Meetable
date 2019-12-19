<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EventCoverImage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('cover_image', 512)->nullable();
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->string('cover_image', 512)->nullable();
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
            $table->dropColumn('cover_image');
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('cover_image');
        });
    }
}
