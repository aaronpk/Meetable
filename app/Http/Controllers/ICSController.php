<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\Tag, App\Setting;
use DateTime, DateTimeZone;
use DB, Log;

class ICSController extends BaseController
{
    private function _addEventToCal(&$vCalendar, &$event) {
        $vEvent = new \Eluceo\iCal\Component\Event();

        $isset = false;

        // start date only
        // full-day events
        if($event->start_date && !$event->start_time && !$event->end_date && !$event->end_time) {
            $isset = true;
            $vEvent->setUseUtc(false); // force floating times
            $vEvent->setDtStart(new DateTime($event->start_date))
                   ->setDtEnd(new DateTime($event->start_date))
                   ->setNoTime(true);
        }
        // start and end date, no time
        // multi-day events
        elseif($event->start_date && !$event->start_time && $event->end_date && !$event->end_time) {
            $isset = true;
            $vEvent->setUseUtc(false); // force floating times
            $vEvent->setDtStart(new DateTime($event->start_date))
                   ->setDtEnd(new DateTime($event->end_date))
                   ->setNoTime(true);
        }
        // start date with only start time
        elseif($event->start_date && $event->start_time && !$event->end_date && !$event->end_time) {
            if($event->timezone) {
                $isset = true;
                $start = new DateTime($event->start_date.' '.$event->start_time, new DateTimeZone($event->timezone));
                $vEvent->setDtStart($start);
            } else {
                $isset = true;
                $vEvent->setUseUtc(false); // force floating times
                $vEvent->setDtStart(new DateTime($event->start_date.' '.$event->start_time));
            }
        }
        // start date with start and end time
        elseif($event->start_date && $event->start_time && !$event->end_date && $event->end_time) {
            if($event->timezone) {
                $isset = true;
                $vEvent->setDtStart(new DateTime($event->start_date.' '.$event->start_time, new DateTimeZone($event->timezone)))
                       ->setDtEnd(new DateTime($event->start_date.' '.$event->end_time, new DateTimeZone($event->timezone)));
            } else {
                $isset = true;
                $vEvent->setUseUtc(false); // force floating times
                $vEvent->setDtStart(new DateTime($event->start_date.' '.$event->start_time))
                       ->setDtEnd(new DateTime($event->start_date.' '.$event->end_time));
            }
        }
        // start and end date and time
        elseif($event->start_date && $event->start_time && $event->end_date && $event->end_time) {
            if($event->timezone) {
                $isset = true;
                $vEvent->setDtStart(new DateTime($event->start_date.' '.$event->start_time, new DateTimeZone($event->timezone)))
                       ->setDtEnd(new DateTime($event->end_date.' '.$event->end_time, new DateTimeZone($event->timezone)));
            } else {
                $isset = true;
                $vEvent->setUseUtc(false); // force floating times
                $vEvent->setDtStart(new DateTime($event->start_date.' '.$event->start_time))
                       ->setDtEnd(new DateTime($event->end_date.' '.$event->end_time));
            }
        }

        if(!$isset) {
            Log::warning('Rendered ics feed with no date for event '.$event->id);
        }

        if(Setting::value('SHOW_RSVPS_IN_ICS')) {
            $summary = $event->name;

            $rsvps = [];
            foreach($event->rsvps_yes as $rsvp) {
                if($name = $rsvp->author_display_name()) {
                    $words = explode(' ', $name);
                    $rsvps[] = $words[0];
                }
            }
            if(count($rsvps)) {
                $summary .= ' ('.implode(', ', $rsvps).')';
            }

            $vEvent->setSummary($summary);
        } else {
            $vEvent->setSummary($event->name);
        }

        $description = strip_tags($event->html());
        if($event->website) {
            $description .= "\n\n" . $event->website;
        }
        $description .= "\n\n" . $event->absolute_permalink();
        $vEvent->setDescription($description);

        $location = $event->location_summary_with_name();
        if(trim($location, ', ')) {
            $vEvent->setLocation($location);
        }

        switch($event->status) {
            case 'confirmed':
              break; // no status needed
            case 'tentative':
            case 'postponed':
              $vEvent->setStatus('TENTATIVE');
              break;
            case 'cancelled':
              $vEvent->setStatus('CANCELLED');
              break;
        }

        $vEvent->setDtStamp(new DateTime($event->created_at));
        $vEvent->setCreated(new DateTime($event->created_at));
        $vEvent->setModified(new DateTime($event->updated_at));

        $vEvent->setUrl($event->absolute_permalink());
        $vEvent->setUniqueId(parse_url(env('APP_URL'), PHP_URL_HOST).'/'.$event->key);

        $vEvent->setMsBusyStatus('free');

        $vCalendar->addComponent($vEvent);
    }

    public function index($year=false, $month=false) {

        $events = Event::orderBy('start_date', 'desc')
            ->get();

        $vCalendar = new \Eluceo\iCal\Component\Calendar(parse_url(env('APP_URL'), PHP_URL_HOST));

        foreach($events as $event) {
            $this->_addEventToCal($vCalendar, $event);
        }

        $ics = $vCalendar->render();

        return response($ics)->withHeaders([
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="events.ics"'
        ]);
    }

    public function tag($tag) {
        $tag = Tag::normalize($tag);

        $events = Event::whereHas('tags', function($query) use ($tag){
            $query->where('tag', $tag);
        })->orderBy('events.start_date', 'desc')->get();

        $vCalendar = new \Eluceo\iCal\Component\Calendar(parse_url(env('APP_URL'), PHP_URL_HOST).' '.$tag);

        foreach($events as $event) {
            $this->_addEventToCal($vCalendar, $event);
        }

        $ics = $vCalendar->render();

        return response($ics)->withHeaders([
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="events.ics"'
        ]);
    }

    public function event($year, $month, $key_or_slug, $key2=false) {

        if($key2) {
            $key = $key2;
            $slug = $key_or_slug;
        } else {
            $key = $key_or_slug;
            $slug = false;
        }

        $event = Event::where('key', $key)->first();

        if(!$event) {
            abort(404);
        }

        // Redirect to the canonical URL
        $date = new DateTime($event->start_date);
        if($event->slug && $event->slug != $slug
           || $year != $date->format('Y')
           || $month != $date->format('m')) {
            return redirect($event->ics_permalink(), 301);
        }

        $vCalendar = new \Eluceo\iCal\Component\Calendar(parse_url(env('APP_URL'), PHP_URL_HOST).$event->permalink());

        $this->_addEventToCal($vCalendar, $event);

        $ics = $vCalendar->render();

        return response($ics)->withHeaders([
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="event.ics"'
        ]);
    }

}
