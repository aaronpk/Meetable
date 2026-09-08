<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('recurrence_interval_count')->nullable();
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->unsignedInteger('recurrence_interval_count')->nullable();
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
            $table->dropColumn('recurrence_interval_count');
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('recurrence_interval_count');
        });
    }
};
