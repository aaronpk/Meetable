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
            $table->boolean('is_template')->default(false);
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->boolean('is_template')->default(false);
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
            $table->dropColumn('is_template');
        });
        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('is_template');
        });
    }
};
