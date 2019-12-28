<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\Tag;
use DateTime, DateTimeZone, Exception;
use DB;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private function events_query($year=false, $month=false, $day=false, $only_future=true) {
        if($year && $month && $day) {
            $events = Event::where('start_date', $year.'-'.$month.'-'.$day);
        } elseif($year && $month) {
            $events = Event::whereYear('start_date', $year)
                ->whereMonth('start_date', $month);
        } elseif($year) {
            $events = Event::whereYear('start_date', $year);
        } elseif($only_future) {
            $events = Event::where('start_date', '>=', date('Y-m-d'));
        } else {
            $events = new Event();
        }

        if($only_future)
            $events = $events->orderBy('start_date');
        else
            $events = $events->orderBy('start_date', 'desc');

        return $events;
    }

    public function index($year=false, $month=false, $day=false) {
        $events = $this->events_query($year, $month, $day);
        $events = $events->get();

        return $this->show_events_from_query($events, [
            'year' => $year,
            'month' => $month,
            'day' => $day,
        ]);
    }

    public function tag($tag) {
        $tag = Tag::normalize($tag);
        $year = $month = false;

        if(request('year') && is_numeric(request('year'))) {
            $year = request('year');
            if(request('month') && is_numeric(request('month')))
                $month = request('month');
        }

        $events = $this->events_query($year, $month);

        $events = $events->whereHas('tags', function($query) use ($tag){
            $query->where('tag', $tag);
        });

        $events = $events->get();

        return $this->show_events_from_query($events, [
            'tag' => $tag,
        ]);
    }

    public function tag_archive($tag) {
        $tag = Tag::normalize($tag);

        $events = $this->events_query(false, false, false, false);

        $events = $events->whereHas('tags', function($query) use ($tag){
            $query->where('tag', $tag);
        });

        $events = $events->get();

        if(count($events) == 0) {
            // TODO: maybe show a page like "no events" instead
            abort(404);
        }

        return $this->show_events_from_query($events, [
            'tag' => $tag,
            'archive' => true,
        ]);
    }

    private function show_events_from_query(&$events, $opts=[]) {
        $data = [];

        foreach($events as $event) {
            $y = date('Y', strtotime($event->start_date));
            $m = (int)date('m', strtotime($event->start_date));

            if(!isset($data[$y]))
                $data[$y] = [];

            if(!isset($data[$y][$m]))
                $data[$y][$m] = [];

            $data[$y][$m][] = $event;
        }

        return view('index', array_merge($opts, [
            'data' => $data,
        ]));
    }

    public function archive() {

        $events = Event::select(DB::raw('YEAR(start_date) as year'), DB::raw('MONTH(start_date) AS month'), 'start_date', 'end_date', 'start_time', 'end_time', 'slug', 'key', 'name')
            ->where('start_date', '<', date('Y-m-d'))
            ->orderBy('start_date', 'desc')
            ->get();

        $data = [];

        foreach($events as $event) {
            if(!isset($data[$event->year]))
                $data[$event->year] = [];

            if(!isset($data[$event->year][$event->month]))
                $data[$event->year][$event->month] = [];

            $data[$event->year][$event->month][] = $event;
        }

        return view('archive', [
            'data' => $data,
        ]);
    }

    public function tags() {

        $query = Tag::join('event_tag', 'tags.id', 'event_tag.tag_id')
            ->groupBy('tag_id')
            ->selectRaw('count(*) as num, tag')
            ->orderBy('num', 'desc')
            ->get();

        $tags = [];
        $max = false;
        foreach($query as $q) {

            if($max === false) // the first one is the max
                $max = $q->num;

            $pct = round($q->num / $max * 100);

            if($pct < 20)
                $class = 'smallest';
            elseif($pct < 40)
                $class = 'small';
            elseif($pct < 60)
                $class = 'medium';
            elseif($pct < 80)
                $class = 'large';
            else
                $class = 'largest';

            $tags[] = [
                'tag' => $q->tag,
                'num' => $q->num,
                'percent' => $pct,
                'class' => $class,
            ];
        }

        shuffle($tags);

        return view('tags', [
            'tags' => $tags,
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
            return redirect($event->permalink(), 301);
        }

        return view('event', [
            'event' => $event,
            'year' => $year,
            'month' => $month,
            'key' => $key,
            'slug' => $slug,
        ]);
    }

    public function add_to_google($key) {
        $event = Event::where('key', $key)->first();

        if(!$event) {
            abort(404);
        }

        $params = ['action' => 'TEMPLATE'];

        $params['text'] = $event->name;
        $params['details'] = $event->absolute_permalink();
        $params['location'] = $event->location_summary_with_name();

        $start = false;
        $end = false;
        if($event->start_date && !$event->start_time && !$event->end_date && !$event->end_time) {
            $start = (new DateTime($event->start_date))->format('Ymd');
        }
        elseif($event->start_date && !$event->start_time && $event->end_date && !$event->end_time) {
            $start = (new DateTime($event->start_date))->format('Ymd');
            $end = (new DateTime($event->end_date))->format('Ymd');
        }
        elseif($event->start_date && $event->start_time && !$event->end_date && !$event->end_time) {
            $start = (new DateTime($event->start_date.' '.$event->start_time))->format('Ymd\THis');
            if($event->timezone) $params['ctz'] = $event->timezone;
        }
        elseif($event->start_date && $event->start_time && !$event->end_date && $event->end_time) {
            $start = (new DateTime($event->start_date.' '.$event->start_time))->format('Ymd\THis');
            $end = (new DateTime($event->start_date.' '.$event->end_time))->format('Ymd\THis');
            if($event->timezone) $params['ctz'] = $event->timezone;
        }
        elseif($event->start_date && $event->start_time && $event->end_date && $event->end_time) {
            $start = (new DateTime($event->start_date.' '.$event->start_time))->format('Ymd\THis');
            $end = (new DateTime($event->end_date.' '.$event->end_time))->format('Ymd\THis');
            if($event->timezone) $params['ctz'] = $event->timezone;
        }

        $params['dates'] = $start . ($end ? '/' . $end : '');

        $url = 'http://www.google.com/calendar/render?' . http_build_query($params);

        return redirect($url, 302);
    }

    public function local_time() {

        try {
            $timezone = new DateTimeZone(request('tz'));
        } catch(Exception $e) {
            $timezone = null;
        }

        try {
            $date = new DateTime(request('date'), $timezone);
        } catch(Exception $e) {
            $date = null;
        }

        $timezones = [];
        foreach(Event::used_timezones() as $tz) {
            $d = new DateTime(request('date'), $timezone);
            $d->setTimeZone(new DateTimeZone($tz->timezone));

            $timezones[] = [
                'name' => $tz->timezone,
                'date' => $d,
            ];
        }

        return view('local-time', [
            'date' => $date,
            'timezone' => $timezone,
            'timezones' => $timezones,
        ]);
    }
}
