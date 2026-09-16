<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A proposed event has no date yet. It offers several candidate dates that people
 * vote on, and the one that is chosen becomes the event's start date.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->date('start_date')->nullable()->change();
            $table->boolean('is_proposed')->default(false);
            $table->unsignedBigInteger('chosen_date_option_id')->nullable();
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->date('start_date')->nullable()->change();
            $table->boolean('is_proposed')->default(false);
        });

        Schema::create('event_date_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->date('date');
            $table->date('end_date')->nullable(); // for multi-day events, which then have no times
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('event_id');
        });

        Schema::create('event_date_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_date_option_id');
            $table->unsignedBigInteger('user_id');
            $table->string('vote', 10); // yes, ifneedbe, no
            $table->timestamps();

            $table->unique(['event_date_option_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('event_date_votes');
        Schema::dropIfExists('event_date_options');

        // Give dateless rows a date before the column goes back to NOT NULL
        DB::table('events')->whereNull('start_date')->update(['start_date' => DB::raw('DATE(created_at)')]);
        DB::table('event_revisions')->whereNull('start_date')->update(['start_date' => DB::raw('DATE(created_at)')]);

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['is_proposed', 'chosen_date_option_id']);
            $table->date('start_date')->nullable(false)->change();
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('is_proposed');
            $table->date('start_date')->nullable(false)->change();
        });
    }
};
