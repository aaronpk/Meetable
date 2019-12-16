<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\Tag;
use DateTime;
use DB;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index($year=false, $month=false) {

        if($year && $month) {
            $events = Event::whereYear('start_date', $year)
                ->whereMonth('start_date', $month)
                ->orderBy('start_date')
                ->get();
        } elseif($year) {
            $events = Event::whereYear('start_date', $year)
                ->orderBy('start_date', 'desc')
                ->get();
        } else {
            $events = Event::where('start_date', '>=', date('Y-m-d'))
                ->orderBy('start_date')
                ->get();
        }

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

        return view('index', [
            'year' => $year,
            'month' => $month,
            'data' => $data,
        ]);
    }

    public function archive() {

        $events = Event::select(DB::raw('YEAR(start_date) as year'), DB::raw('MONTH(start_date) AS month'), 'start_date', 'slug', 'key', 'name')
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
        }
        elseif($event->start_date && $event->start_time && !$event->end_date && $event->end_time) {
            $start = (new DateTime($event->start_date.' '.$event->start_time))->format('Ymd\THis');
            $end = (new DateTime($event->start_date.' '.$event->end_time))->format('Ymd\THis');
        }
        elseif($event->start_date && $event->start_time && $event->end_date && $event->end_time) {
            $start = (new DateTime($event->start_date.' '.$event->start_time))->format('Ymd\THis');
            $end = (new DateTime($event->end_date.' '.$event->end_time))->format('Ymd\THis');
        }

        $params['dates'] = $start . ($end ? '/' . $end : '');

        $url = 'http://www.google.com/calendar/render?' . http_build_query($params);

        return redirect($url, 302);
    }

    public function tag($tag) {
        $tag = Tag::normalize($tag);

        $events = Event::whereHas('tags', function($query) use ($tag){
            $query->where('tag', $tag);
        })->orderBy('events.start_date', 'desc')->get();

        if(count($events) == 0) {
            abort(404);
        }

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

        return view('index', [
            'tag' => $tag,
            'data' => $data,
        ]);
    }
}
