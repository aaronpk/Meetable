<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\Tag;
use DateTime;
use DB;

class ICSController extends BaseController
{
    private function _addEventToCal(&$vCalendar, &$event) {
        $vEvent = new \Eluceo\iCal\Component\Event();

        $vEvent->setUseUtc(false); // force floating times

        // start date only
        // full-day events
        if($event->start_date && !$event->start_time && !$event->end_date && !$event->end_time) {
            $vEvent->setDtStart(new DateTime($event->start_date))
                   ->setDtEnd(new DateTime($event->start_date))
                   ->setNoTime(true);
        }
        // start and end date, no time
        // multi-day events
        elseif($event->start_date && !$event->start_time && $event->end_date && !$event->end_time) {
            header('X-Start-Date: '.$event->start_date);
            header('X-End-Date: '.$event->end_date);
            $vEvent->setDtStart(new DateTime($event->start_date))
                   ->setDtEnd(new DateTime($event->end_date))
                   ->setNoTime(true);
        }
        // start date with only start time
        elseif($event->start_date && $event->start_time && !$event->end_date && !$event->end_time) {
            $vEvent->setDtStart(new DateTime($event->start_date.' '.$event->start_time));
        }
        // start date with start and end time
        elseif($event->start_date && $event->start_time && !$event->end_date && $event->end_time) {
            $vEvent->setDtStart(new DateTime($event->start_date.' '.$event->start_time))
                   ->setDtEnd(new DateTime($event->start_date.' '.$event->end_time));
        }
        // start and end date and time
        elseif($event->start_date && $event->start_time && $event->end_date && $event->end_time) {
            $vEvent->setDtStart(new DateTime($event->start_date.' '.$event->start_time))
                   ->setDtEnd(new DateTime($event->end_date.' '.$event->end_time));
        }

        $vEvent->setSummary($event->name);

        $description = $event->absolute_permalink() . "\n\n"
            . $event->description;
        if($event->website) {
            $description .= "\n\n" . $event->website;
        }
        $vEvent->setDescription($description);

        $location = $event->location_summary_with_name();
        if(trim($location, ', ')) {
            $vEvent->setLocation($location);
        }

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

}
