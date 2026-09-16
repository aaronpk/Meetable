<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Log;
use App\DiscordNotification, App\DiscordNotificationSend;
use App\Services\Discord, App\Services\DiscordException;
use DateTime, DateTimeZone;

class SendDiscordNotifications extends Command {

    protected $signature = 'discord:notify';
    protected $description = 'Post reminders to Discord channels for upcoming events';

    public function handle() {

        // This job is scheduled to run every minute.
        // For each tag/channel mapping, post about events starting within its time window.

        if(!Discord::enabled() || !Discord::botConfigured())
            return;

        $now = new DateTime('now', new DateTimeZone('UTC'));

        $notifications = DiscordNotification::where('enabled', 1)->get();

        foreach($notifications as $notification) {
            foreach($notification->eventsDue($now) as $event) {
                $this->info('Posting to #'.$notification->channel_name.' for '.$event->name.' '.$event->absolute_permalink());

                try {
                    $message_id = Discord::sendMessage($notification->channel_id, Discord::buildEventMessage($notification, $event));
                } catch(DiscordException $e) {
                    // Nothing is recorded, so this will be retried next minute while the event is still upcoming
                    Log::error('Discord notification '.$notification->id.' for event '.$event->id.' failed: '.$e->getMessage());
                    $this->error($e->getMessage());
                    continue;
                }

                $send = new DiscordNotificationSend;
                $send->discord_notification_id = $notification->id;
                $send->event_id = $event->id;
                $send->event_start = DiscordNotification::eventStart($event);
                $send->discord_message_id = $message_id;
                $send->save();
            }
        }
    }

}
