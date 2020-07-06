<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RevisionEventId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->bigInteger('event_id')->index()->after('id');
        });

        DB::update('UPDATE event_revisions
            JOIN events ON events.key = event_revisions.key
            SET event_revisions.event_id = events.id');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('event_id');
        });
    }
}
