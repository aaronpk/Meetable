<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A proposed event has no time to schedule a Zoom meeting at, so the request is
 * remembered here and the meeting is scheduled once a date is chosen.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('zoom_meeting_requested')->default(false);
        });
    }

    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('zoom_meeting_requested');
        });
    }
};
