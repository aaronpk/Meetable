<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Discord scheduled event created for an event, and a fingerprint of what was last
 * sent to Discord so the sync command can tell when the Discord copy needs updating.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('discord_scheduled_event_id', 30)->default('');
            $table->string('discord_scheduled_event_hash', 64)->default('');
            $table->index('discord_scheduled_event_id');
        });
    }

    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['discord_scheduled_event_id']);
            $table->dropColumn(['discord_scheduled_event_id', 'discord_scheduled_event_hash']);
        });
    }
};
