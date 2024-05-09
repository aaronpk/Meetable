<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\Tag, App\Setting;
use DateTime, DateTimeZone, DateInterval;
use DB, Log;
use Illuminate\Http\Request;

class ICSController extends BaseController
{
    private function _addEventToCal(&$vCalendar, &$event) {
        $vEvent = new \Eluceo\iCal\Component\Event();

        $isset = false;

        if($event->is_multiday()) {
            $isset = true;
            $vEvent->setUseUtc(false); // force floating times
            $vEvent->setDtStart(new DateTime($event->start_date))
                   ->setDtEnd(new DateTime($event->end_date))
                   ->setNoTime(true);
        } else {

            // start date with start and end time
            if($event->start_date && $event->start_time && $event->end_time) {
                $isset = true;

                if($event->timezone) {
                    $vEvent->setDtStart($event->start_datetime(), new DateTimeZone($event->timezone))
                           ->setDtEnd($event->end_datetime(), new DateTimeZone($event->timezone));
                } else {
                    $vEvent->setUseUtc(false); // force floating times
                    $vEvent->setDtStart($event->start_datetime())
                           ->setDtEnd($event->end_datetime());
                }
            }
            // start date with only start time
            elseif($event->start_date && $event->start_time && !$event->end_time) {
                if($event->timezone) {
                    $isset = true;
                    $vEvent->setDtStart($event->start_datetime());
                } else {
                    $isset = true;
                    $vEvent->setUseUtc(false); // force floating times
                    $vEvent->setDtStart($event->start_datetime());
                }
            }
            // start date only
            // full-day events
            elseif($event->start_date && !$event->start_time && !$event->end_time) {
                $isset = true;
                $vEvent->setUseUtc(false); // force floating times
                $vEvent->setDtStart(new DateTime($event->start_date))
                       ->setDtEnd(new DateTime($event->start_date))
                       ->setNoTime(true);
            }

        }

        if(!$isset) {
            Log::warning('Rendered ics feed with no date for event '.$event->id);
        }

        if(Setting::value('show_rsvps_in_ics')) {
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

        if(Setting::value('show_meeting_url_in_ics')) {
            $description .= "\n\n" . $event->meeting_url;
        }

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

        // Include the last updated date in the UID, since google apparently
        // doesn't update properties from the ics after it's seen it once.
        $timestamp = strtotime($event->updated_at);
        $host = parse_url(env('APP_URL'), PHP_URL_HOST);
        $vEvent->setUniqueId($event->key.'-'.$timestamp.'@'.$host);

        $vEvent->setMsBusyStatus('free');

        $vCalendar->addComponent($vEvent);
    }

    private function _isRequestFromBrowser(Request $request) {
        return 'text/html' === $request->prefers(['text/calendar', 'text/html']);
    }

    public function index(Request $request) {
        $events = Event::orderBy('start_date', 'desc')
            ->where('unlisted', 0)
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

    public function tag(Request $request, $tag) {
        $tag = Tag::normalize($tag);

        $events = Event::whereHas('tags', function($query) use ($tag){
            $query->where('tag', $tag);
        })
        ->where('unlisted', 0)
        ->orderBy('events.start_date', 'desc')->get();

        $vCalendar = new \Eluceo\iCal\Component\Calendar(parse_url(env('APP_URL'), PHP_URL_HOST).' '.$tag);

        foreach($events as $event) {
            $this->_addEventToCal($vCalendar, $event);
        }

        $ics = $vCalendar->render();

        return response($ics)->withHeaders([
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="events-'.$tag.'.ics"'
        ]);
    }

    public function preview(Request $request) {
        $url = str_replace('/preview', '', $request->url());

        return view('ics', [
            'url' => $url,
        ]);
    }

    public function event(Request $request, $year, $month, $key_or_slug, $key2=false) {

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
            'Content-Disposition' => 'attachment; filename="event-'.$event->key.'.ics"'
        ]);
    }

    public static function hms_to_sec($hms) {
        $parts = explode(':', $hms);
        return $parts[2] + ($parts[1]*60) + ($parts[0]*60*60);
    }

}
