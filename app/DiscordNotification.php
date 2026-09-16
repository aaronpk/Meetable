<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DateTime, DateInterval;

class DiscordNotification extends Model
{
    public static $TIME_UNITS = [
        'minutes' => 1,
        'hours' => 60,
        'days' => 1440,
    ];

    const MAX_MINUTES_BEFORE = 43200; // 30 days

    public function sends() {
        return $this->hasMany('\App\DiscordNotificationSend');
    }

    public function createdBy() {
        return $this->belongsTo('\App\User', 'created_by');
    }

    public function lastModifiedBy() {
        return $this->belongsTo('\App\User', 'last_modified_by');
    }

    // Events with this tag that should be announced
    public function eventsQuery() {
        $tag = $this->tag;
        return Event::whereHas('tags', function($query) use($tag){
                $query->where('tag', $tag);
            })
            ->where('unlisted', 0)
            ->where('is_template', 0)
            ->whereNotIn('status', ['cancelled', 'postponed'])
            ->whereNotNull('sort_date');
    }

    // Events that start within the notification window and haven't been announced yet at their current start time
    public function eventsDue(DateTime $now) {
        $until = clone $now;
        $until->add(new DateInterval('PT'.$this->minutes_before.'M'));

        $events = $this->eventsQuery()
            ->where('sort_date', '>', $now->format('Y-m-d H:i:s'))
            ->where('sort_date', '<=', $until->format('Y-m-d H:i:s'))
            ->orderBy('sort_date', 'asc')
            ->get();

        return $events->filter(function($event){
            return !$this->wasSentFor($event);
        });
    }

    public function nextEvent() {
        return $this->eventsQuery()
            ->where('sort_date', '>', gmdate('Y-m-d H:i:s'))
            ->orderBy('sort_date', 'asc')
            ->first();
    }

    public function wasSentFor(Event $event) {
        return $this->sends()
            ->where('event_id', $event->id)
            ->where('event_start', self::eventStart($event))
            ->exists();
    }

    // The event's UTC start as stored in sort_date
    public static function eventStart(Event $event) {
        if($event->sort_date instanceof \DateTimeInterface)
            return $event->sort_date->format('Y-m-d H:i:s');
        return (new DateTime($event->sort_date))->format('Y-m-d H:i:s');
    }

    // Returns [number, unit] for displaying minutes_before in the largest unit it divides evenly into
    public function timeBeforeParts() {
        foreach(array_reverse(self::$TIME_UNITS) as $unit => $minutes) {
            if($this->minutes_before && $this->minutes_before % $minutes == 0)
                return [$this->minutes_before / $minutes, $unit];
        }
        return [$this->minutes_before, 'minutes'];
    }

    public function timeBeforeText() {
        list($number, $unit) = $this->timeBeforeParts();
        return trans_choice('discord.durations.'.$unit, $number);
    }
}
