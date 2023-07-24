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
            $table->boolean('hide_from_main_feed')->default(0);
            $table->bigInteger('parent_id')->nullable();
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->boolean('hide_from_main_feed')->default(0);
            $table->bigInteger('parent_id')->nullable();
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
            $table->dropColumn('hide_from_main_feed');
            $table->dropColumn('parent_id');
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('hide_from_main_feed');
            $table->dropColumn('parent_id');
        });
    }
};
