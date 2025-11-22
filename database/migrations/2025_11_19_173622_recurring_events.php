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
            $table->string('recurrence_interval')->nullable();
            $table->bigInteger('created_from_template_event_id')->nullable();
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->string('recurrence_interval')->nullable();
            $table->bigInteger('created_from_template_event_id')->nullable();
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
            $table->dropColumn('recurrence_interval');
            $table->dropColumn('created_from_template_event_id');
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('recurrence_interval');
            $table->dropColumn('created_from_template_event_id');
        });
    }
};
