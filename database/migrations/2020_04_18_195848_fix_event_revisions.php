<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixEventRevisions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->datetime('sort_date')->nullable()->after('timezone');
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('photo_order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('sort_date');
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->text('photo_order')->nullable();
        });
    }
}
