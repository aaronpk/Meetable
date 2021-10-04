<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInboundEmailLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inbound_email_log', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('status', 50);
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('event_id')->nullable();
            $table->longtext('raw_ics');
            $table->longtext('raw_body');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inbound_email_log');
    }
}
