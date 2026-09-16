<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The date a recurring event template scheduled an occurrence for. It stays the
     * same if the occurrence is moved to another date, so the scheduler can tell the
     * date already has an occurrence.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->date('created_from_template_date')->nullable()->after('created_from_template_event_id');
        });

        // Existing occurrences were created for the date they're on. Deleted ones are
        // included so the scheduler doesn't bring them back.
        DB::table('events')
            ->whereNotNull('created_from_template_event_id')
            ->update(['created_from_template_date' => DB::raw('start_date')]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('created_from_template_date');
        });
    }
};
