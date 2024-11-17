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
            $table->string('notes_url', 512)->default('');
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->string('notes_url', 512)->default('');
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
            $table->dropColumn('notes_url');
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('notes_url');
        });
    }
};
