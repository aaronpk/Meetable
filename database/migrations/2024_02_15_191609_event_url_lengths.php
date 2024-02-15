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
            $table->longtext('website')->nullable()->change();
            $table->longtext('tickets_url')->nullable()->change();
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->longtext('website')->nullable()->change();
            $table->longtext('tickets_url')->nullable()->change();
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
            $table->string('website', 512)->nullable()->change();
            $table->string('tickets_url', 512)->nullable()->change();
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->string('website', 512)->nullable()->change();
            $table->string('tickets_url', 512)->nullable()->change();
        });
    }
};
