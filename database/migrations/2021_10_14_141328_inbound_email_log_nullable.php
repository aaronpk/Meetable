<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InboundEmailLogNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inbound_email_log', function (Blueprint $table) {
            $table->longtext('raw_ics')->nullable()->change();
            $table->longtext('raw_body')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inbound_email_log', function (Blueprint $table) {
            $table->longtext('raw_ics')->nullable(false)->change();
            $table->longtext('raw_body')->nullable(false)->change();
        });
    }
}
