<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VirtualMeetingLink extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->longtext('meeting_url')->nullable()->after('location_country');
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->longtext('meeting_url')->nullable()->after('location_country');
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
            $table->dropColumn('meeting_url');
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('meeting_url');
        });
    }
}
