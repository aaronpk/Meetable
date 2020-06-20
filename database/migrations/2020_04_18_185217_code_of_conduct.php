<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CodeOfConduct extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->longtext('code_of_conduct_url')->nullable()->after('tickets_url');
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->longtext('code_of_conduct_url')->nullable()->after('tickets_url');
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
            $table->dropColumn('code_of_conduct_url');
        });

        Schema::table('event_revisions', function (Blueprint $table) {
            $table->dropColumn('code_of_conduct_url');
        });
    }
}
