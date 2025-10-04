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
            $table->bigInteger('cloned_from_id')->nullable();
            $table->datetime('previous_instance_date')->nullable()->after('sort_date');
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->bigInteger('cloned_from_id')->nullable();
            $table->datetime('previous_instance_date')->nullable()->after('sort_date');
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
            $table->dropColumn('cloned_from_id');
            $table->dropColumn('previous_instance_date');
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('cloned_from_id');
            $table->dropColumn('previous_instance_date');
        });
    }
};
