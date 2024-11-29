<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixEventNotesUrl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->longtext('notes_url')->nullable()->default('')->change();
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->longtext('notes_url')->nullable()->default('')->change();
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
            $table->longtext('notes_url')->nullable(false)->default('')->change();
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->longtext('notes_url')->nullable(false)->default('')->change();
        });
    }
}
