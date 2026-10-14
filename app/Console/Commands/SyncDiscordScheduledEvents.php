<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Log;
use App\Event, App\DiscordNotification, App\Setting;
use App\Services\Discord, App\Services\DiscordException;
use DateTime, DateTimeZone;

/**
 * Keeps the Discord server's event list matching the website's upcoming events.
 *
 * Runs every minute. Rather than reacting to each change on the website, it compares
 * the events that should be in Discord with the ones that are, so it also covers
 * deletions, recurring events and anything else that doesn't go through the event
 * form, and a failed request is simply tried again on the next run.
 */
class SyncDiscordScheduledEvents extends Command {

    protected $signature = 'discord:sync-events';
    protected $description = 'Keep the Discord server\'s event list in sync with upcoming website events';

    // Discord won't create an event that starts in the past, so leave a little room
    const START_BUFFER_MINUTES = 2;

    // How far ahead events are added unless the site chooses otherwise
    const DEFAULT_DAYS_AHEAD = 60;
    const MAX_DAYS_AHEAD = 365;

    // Only this many of the soonest events are added, however far ahead the site looks, so a
    // busy calendar doesn't fill the server's event list (Discord allows 100) or hit rate limits
    const MAX_EVENTS = 25;

    // Discord rate limits writes, so a run only makes this many and the rest wait for the next minute
    const MAX_WRITES_PER_RUN = 10;

    private $errors = [];
    private $writes = 0;

    // Whether this site can talk to a Discord server at all
    public static function available() {
        return Discord::enabled() && Discord::botConfigured() && Discord::guildId();
    }

    // How many days ahead to look for events to add
    public static function daysAhead() {
        $days = (int)Setting::value('discord_sync_events_days');
        return ($days >= 1 && $days <= self::MAX_DAYS_AHEAD) ? $days : self::DEFAULT_DAYS_AHEAD;
    }

    // The tags an event needs one of to be added, or an empty list for every public event
    public static function tags() {
        return array_values(array_filter(explode(' ', (string)Setting::value('discord_sync_events_tags'))));
    }

    public function handle() {
        // The same command instance can be run more than once in one process
        $this->errors = [];
        $this->writes = 0;

        if(!self::available())
            return;

        $now = new DateTime('now', new DateTimeZone('UTC'));
        $cutoff = (clone $now)->modify('+'.self::START_BUFFER_MINUTES.' minutes')->format('Y-m-d H:i:s');
        $horizon = (clone $now)->modify('+'.self::daysAhead().' days')->format('Y-m-d H:i:s');

        // Everything that belongs in Discord, soonest first. When the option is turned off
        // nothing belongs there, so anything added earlier is removed below.
        if(Setting::value('discord_sync_events')) {
            $query = Event::announceable()
                ->where('sort_date', '>', $cutoff)
                ->where('sort_date', '<=', $horizon)
                ->orderBy('sort_date');
            if($tags = self::tags())
                $query->withAnyTag($tags);
            $eligible = $query->get();
        } else {
            $eligible = collect();
        }

        // What this run will create or update: the soonest few. The rest are left alone, not
        // removed, and get their turn as sooner events finish.
        $wanted = $eligible->take(self::MAX_EVENTS);

        // First remove what no longer belongs, so the server's count goes down before it goes up
        $stale = Event::withTrashed()
            ->where('discord_scheduled_event_id', '!=', '')
            ->whereNotIn('id', $eligible->pluck('id'))
            ->get();

        foreach($stale as $event) {
            // Discord moves a started event through active and completed by itself, and
            // won't let it change afterwards, so there is nothing to do but stop tracking it
            if(DiscordNotification::eventStart($event) <= $cutoff) {
                $this->forget($event);
                continue;
            }

            if($this->writes >= self::MAX_WRITES_PER_RUN)
                break;
            $this->writes++;

            try {
                if(!$event->trashed() && in_array($event->status, ['cancelled', 'postponed'])) {
                    $this->info('Cancelling the Discord event for '.$event->name);
                    Discord::cancelScheduledEvent($event->discord_scheduled_event_id, 'Event '.$event->status.' on '.env('APP_URL'));
                } else {
                    $this->info('Deleting the Discord event for '.$event->name);
                    Discord::deleteScheduledEvent($event->discord_scheduled_event_id, 'Event removed from '.env('APP_URL'));
                }
            } catch(DiscordException $e) {
                // Already gone from Discord is as good as deleted
                if($e->getCode() != Discord::ERROR_UNKNOWN_SCHEDULED_EVENT) {
                    if($this->failed($event, $e))
                        return $this->finish();
                    continue;
                }
            }

            $this->forget($event);
        }

        foreach($wanted as $event) {
            $payload = Discord::scheduledEventPayload($event);
            $hash = Discord::scheduledEventHash($event, $payload);

            if($event->discord_scheduled_event_id && $hash === $event->discord_scheduled_event_hash)
                continue;

            if($this->writes >= self::MAX_WRITES_PER_RUN)
                break;
            $this->writes++;

            if($image = Discord::scheduledEventImage($event))
                $payload['image'] = $image;

            try {
                if($event->discord_scheduled_event_id) {
                    $this->info('Updating the Discord event for '.$event->name);
                    try {
                        Discord::updateScheduledEvent($event->discord_scheduled_event_id, $payload, 'Event updated on '.env('APP_URL'));
                    } catch(DiscordException $e) {
                        if($e->getCode() != Discord::ERROR_UNKNOWN_SCHEDULED_EVENT)
                            throw $e;
                        // Someone deleted it in Discord. The event is still upcoming, so make a new one.
                        $event->discord_scheduled_event_id = '';
                    }
                }

                if(!$event->discord_scheduled_event_id) {
                    $this->info('Creating the Discord event for '.$event->name);
                    $event->discord_scheduled_event_id = Discord::createScheduledEvent($payload, 'Event on '.env('APP_URL'));
                }
            } catch(DiscordException $e) {
                if($this->failed($event, $e))
                    return $this->finish();
                continue;
            }

            $this->store($event, $event->discord_scheduled_event_id, $hash);
        }

        $this->finish();
    }

    // Written directly so the event's updated_at and anything listening for saves are left alone
    private function store(Event $event, $id, $hash) {
        Event::withTrashed()->where('id', $event->id)->update([
            'discord_scheduled_event_id' => $id,
            'discord_scheduled_event_hash' => $hash,
        ]);
    }

    private function forget(Event $event) {
        $this->store($event, '', '');
    }

    // Logs the failure. Returns true when the rest of the run would fail the same way.
    private function failed(Event $event, DiscordException $e) {
        Log::error('Discord scheduled event for event '.$event->id.' failed: '.$e->getMessage());
        $this->error($e->getMessage());
        $this->errors[] = $e->getMessage();
        return Discord::isPermissionError($e);
    }

    // Remembers the last error for the Discord settings page. Only written when it changes,
    // since every change to a setting is kept as a revision.
    private function finish() {
        $message = $this->errors ? end($this->errors) : '';
        if((string)Setting::value('discord_scheduled_events_error') !== $message) {
            Setting::set('discord_scheduled_events_error', $message);
            Setting::$cached = [];
        }
    }

}
