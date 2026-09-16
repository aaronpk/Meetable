<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * One of the candidate dates of a proposed event, with the votes people have cast on it.
 */
class EventDateOption extends Model
{
    public static $VOTES = ['yes', 'ifneedbe', 'no'];

    protected $hidden = ['event_id'];

    public function event() {
        return $this->belongsTo('\App\Event', 'event_id');
    }

    public function votes() {
        return $this->hasMany('\App\EventDateVote', 'event_date_option_id');
    }

    public function vote_for_user(User $user) {
        return $this->votes->firstWhere('user_id', $user->id);
    }

    /**
     * How many people answered yes, if need be and no.
     *
     * Reads the counts loaded by Event::date_options_with_tallies() when they are
     * there, otherwise counts the votes themselves.
     */
    public function vote_counts() {
        $counts = [];
        foreach(self::$VOTES as $vote) {
            if(isset($this->attributes[$vote.'_count']))
                $counts[$vote] = (int)$this->attributes[$vote.'_count'];
            else
                $counts[$vote] = $this->votes->where('vote', $vote)->count();
        }
        return $counts;
    }

    // The users who gave this answer, for showing who can make it
    public function voters($vote) {
        return $this->votes->where('vote', $vote)->map(function($v){
            return $v->user;
        })->filter()->values();
    }

    /**
     * A throwaway event on this date, so a candidate date is formatted exactly like
     * a scheduled event's date would be.
     */
    public function as_event() {
        $event = new Event;
        $event->start_date = $this->date;
        $event->end_date = $this->end_date;
        $event->start_time = $this->start_time;
        $event->end_time = $this->end_time;
        if($this->event) {
            $event->timezone = $this->event->timezone;
            $event->location_name = $this->event->location_name;
        }
        return $event;
    }

    public function is_multiday() {
        return $this->end_date && $this->end_date != $this->date;
    }

    public function display_date($with_weekday = true) {
        return $this->as_event()->display_date($with_weekday);
    }

    public function display_time() {
        return $this->as_event()->display_time();
    }

    public function date_summary() {
        return $this->as_event()->date_summary();
    }

    public function start_datetime() {
        return $this->as_event()->start_datetime();
    }

    public function end_datetime() {
        return $this->as_event()->end_datetime();
    }
}
