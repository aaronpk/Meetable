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
        Schema::create('discord_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('tag');
            $table->string('channel_id', 30);
            $table->string('channel_name')->default('');
            $table->unsignedInteger('minutes_before');
            $table->text('message')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('last_modified_by')->nullable();
            $table->timestamps();

            $table->index('tag');
        });

        Schema::create('discord_notification_sends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discord_notification_id');
            $table->unsignedBigInteger('event_id');
            // The event's sort_date at the time the message was posted, so a rescheduled event gets a new reminder
            $table->datetime('event_start');
            $table->string('discord_message_id', 30)->nullable();
            $table->timestamps();

            $table->unique(['discord_notification_id', 'event_id', 'event_start'], 'discord_notification_sends_unique');
            $table->index('event_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discord_notification_sends');
        Schema::dropIfExists('discord_notifications');
    }
};
