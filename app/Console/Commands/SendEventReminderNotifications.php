<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Log;
use App\Event;
use App\Helpers\Notification;
use Illuminate\Support\Str;
use DateTime, DateInterval;

class SendEventReminderNotifications extends Command {

    protected $signature = 'event:notify';
    protected $description = 'Send notifications for upcoming events';

    public function handle() {

        // This job is scheduled to run every minute.
        // Look for any upcoming events with a meeting URL in the next 10 minutes.

        $now = new DateTime();
        $future = new DateTime();
        $future->add(DateInterval::createFromDateString('10 minutes'));

        $this->info('Now: '.$now->format('c'));
        $this->info('+10: '.$future->format('c'));

        $events = Event::whereNotNull('meeting_url')
          ->whereNotNull('start_time')
          ->where('notification_sent', 0)
          ->where('sort_date', '>', $now->format('Y-m-d H:i:s'))
          ->where('sort_date', '<', $future->format('Y-m-d H:i:s'))
          ->get();

        foreach($events as $event) {
            $this->info('Sending notification for ' . $event->name . ' ' . $event->absolute_shortlink());
            Notification::sendPrimary($event->name . ' is starting soon! Join us! ' . $event->absolute_shortlink());
            $event->notification_sent = 1;
            $event->save();
        }
    }

}
