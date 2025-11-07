<?php
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\Tag, App\Setting;
use DateTime, DateTimeZone, DateInterval, Exception;
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
            // Use last possible timezone when finding events on the same day.
            // This will incorrectly show some events that have passed in some far forward timezones.
            // All-day events don't have timezone info anyway, so that's the best we can do.
            $now = new DateTime('now', new DateTimeZone('-12:00'));
            $nowDate = $now->format('Y-m-d');

            $events = Event::where(function($query)use($nowDate){
                $query->where('start_date', '>=', $nowDate)
                      ->orWhere('end_date', '>=', $nowDate);
            });
        } else {
            $events = new Event();
        }

        $events = $events->where('unlisted', 0)->where('hide_from_main_feed', 0);

        if($only_future)
            $events = $events->orderBy('sort_date');
        else
            $events = $events->orderBy('sort_date', 'desc');

        return $events;
    }

    public function index($year=false, $month=false, $day=false) {
        $events = $this->events_query($year, $month, $day);
        $event_ids = $events->pluck('id');
        $events = $events->get();

        $tags = [];
        if(count($events) > 0) {
            $query = DB::select(DB::raw('SELECT tag, COUNT(1) AS cities_count, SUM(num) AS events_count
                FROM
                (SELECT tags.tag, events.location_locality AS locality, COUNT(1) AS num
                FROM events
                JOIN event_tag ON event_tag.event_id = events.id
                JOIN tags ON event_tag.tag_id = tags.id
                WHERE events.id IN ('.implode(',', $event_ids->all()).')
                GROUP BY tag, locality
                ORDER BY tag) AS data
                GROUP BY tag
                ORDER BY cities_count DESC, tag
                '));
            foreach($query as $tag) {
                // Only show tags used by more than 1 event, otherwise the list is very
                // long and it isn't very interesting to click a tag and see just one event
                if($tag->events_count > 1)
                    $tags[] = $tag;
            }
        }

        return $this->show_events_from_query($events, [
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'tags' => $tags,
            'home' => (!$year && !$month && !$day)
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

        if(count($events) <= 1) {
            $now = new DateTime('now', new DateTimeZone('+12:00')); // opposite offset compared to looking for future events
            $nowDate = $now->format('Y-m-d');

            $past_events = $this->events_query(false, false, false, false);

            $past_events = $past_events->whereHas('tags', function($query) use ($tag){
                $query->where('tag', $tag);
            })->where(function($query)use($nowDate){
                $query->where('start_date', '<=', $nowDate)
                      ->orWhere('end_date', '<=', $nowDate);
            })->limit(6);

            $past_events = $past_events->get();
        } else {
            $past_events = [];
        }

        return $this->show_events_from_query($events, [
            'tag' => $tag,
            'past_events' => $past_events,
            'page_title' => 'Upcoming events tagged #'.$tag,
        ]);
    }

    public function year_tag($year, $tag) {
        $tag = Tag::normalize($tag);

        $now = new DateTime('now', new DateTimeZone('-12:00'));
        $nowDate = $now->format('Y-m-d');

        $upcoming_events = $this->events_query($year);
        $upcoming_events = $upcoming_events->where(function($query)use($nowDate){
            $query->where('start_date', '>=', $nowDate)
                  ->orWhere('end_date', '>=', $nowDate);
        });

        $upcoming_events = Event::tagged($upcoming_events, $tag);

        $upcoming_event_ids = $upcoming_events->pluck('id');
        $upcoming_events = $upcoming_events->get();

        $past_events = $this->events_query($year, false, false, false);
        $past_events = $past_events->whereRaw(DB::raw('
            ((start_date < "'.$nowDate.'" AND end_date IS NULL)
            OR
            (end_date < "'.$nowDate.'"))
        '));
        $past_events = Event::tagged($past_events, $tag);
        $past_events = $past_events->get();

        if(count($upcoming_events) == 0) {
            $upcoming_events = $past_events->reverse();
            $past_events = null;
        }

        return $this->show_events_from_query($upcoming_events, [
            'archive' => true,
            'past_events' => $past_events,
            'year' => $year,
            'month' => false,
            'day' => false,
            'home' => (!$year && !$month && !$day),
            'page_title' => $year . ' #' . $tag . ' Events'
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
            'page_title' => 'Events tagged #'.$tag,
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

        if(isset($opts['past_events'])) {
            $past_events = [];

            foreach($opts['past_events'] as $event) {
                $y = date('Y', strtotime($event->start_date));
                $m = (int)date('m', strtotime($event->start_date));

                if(!isset($past_events[$y]))
                    $past_events[$y] = [];

                if(!isset($past_events[$y][$m]))
                    $past_events[$y][$m] = [];

                $past_events[$y][$m][] = $event;
            }

            $opts['past_events'] = $past_events;
        }

        if(!isset($opts['page_title'])) {
            if(!empty($opts['day'])) {
                $opts['page_title'] = env('APP_NAME').' on '.date('F j, Y', strtotime($opts['year'].'-'.$opts['month'].'-'.$opts['day']));
            } elseif(!empty($opts['month'])) {
                $opts['page_title'] = env('APP_NAME').' in '.date('F Y', strtotime($opts['year'].'-'.$opts['month'].'-01'));
            } elseif(!empty($opts['year'])) {
                $opts['page_title'] = env('APP_NAME').' in '.$opts['year'];
            } else {
                $opts['page_title'] = env('APP_NAME');
            }
        }

        return view('index', array_merge($opts, [
            'data' => $data,
        ]));
    }

    public function archive() {

        // Use furthest ahead timezone to find past events.
        // This will incorrectly show some current events that are in far negative timezones, but that's fine.
        $now = new DateTime('now', new DateTimeZone('+12:00'));

        $events = Event::select(DB::raw('YEAR(start_date) as year'), DB::raw('MONTH(start_date) AS month'), 'start_date', 'end_date', 'start_time', 'end_time', 'slug', 'key', 'name', 'status', 'parent_id')
            ->where('start_date', '<', $now->format('Y-m-d'))
            ->where('unlisted', 0)
            ->orderBy('sort_date', 'desc')
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

        // $query = Tag::join('event_tag', 'tags.id', 'event_tag.tag_id')
        //     ->groupBy('tag')
        //     ->selectRaw('count(*) as num, tag')
        //     ->orderBy('num', 'desc')
        //     ->get();

        // Group tags by the number of different cities they are used in, and sort by the number of events.
        // This should produce a list where the first tags are the most broad/common across many cities,
        // and the tags lower down in the list are usually city-specific.
        $query = DB::select(DB::raw('SELECT tag, COUNT(1) AS num_cities, SUM(num) AS num_events
            FROM
            (SELECT tags.tag, events.location_locality AS locality, COUNT(1) AS num
            FROM events
            JOIN event_tag ON event_tag.event_id = events.id
            JOIN tags ON event_tag.tag_id = tags.id
            GROUP BY tag, locality
            ORDER BY tag) AS data
            GROUP BY tag
            ORDER BY num_cities DESC, tag
            '));

        $tags = [];
        $max = false;
        foreach($query as $q) {

            if($max === false) // the first one is the max
                $max = $q->num_events;

            $pct = round($q->num_events / $max * 100);

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
                'num' => $q->num_events,
                'percent' => $pct,
                'class' => $class,
            ];
        }

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

        $event = Event::find_from_url(parse_url(request()->url(), PHP_URL_PATH));

        if(!$event) {
            // Check for fuzzy matches, and either redirect to the event or show the list of matching events
            return $this->find_matching_events($year, $month, $key);
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
            'mode' => 'event',
            'page_title' => $event->name . ' | ' . $event->display_date(),
        ]);
    }

    public function event_json($key) {
        $event = Event::where('key', $key)->first();
        if(!$event) {
            abort(404);
        }

        $meeting_url = false;

        if($event->meeting_url && !$event->is_past() && $event->is_starting_soon()) {
            $meeting_url = $event->meeting_url;
        }

        return response()->json([
            'meeting_url' => $meeting_url,
        ]);
    }

    public function export_event_json(Event $event, $secretkey) {
        if($event->export_secret != $secretkey) {
            abort(403);
        }

        return response()->json([
            'generator' => 'Meetable',
            'version' => '1.0',
            'event' => $event,
        ]);
    }

    public function event_shorturl($key) {
        return $this->event(0, 0, $key);
    }

    public function find_matching_events($year, $month, $partial_slug) {
        $events = Event::whereYear('start_date', $year)
          ->whereMonth('start_date', $month)
          ->where('slug', 'like', $partial_slug.'%')
          ->get();

        if($events->count() == 0) {
            abort(404);
        }

        if($events->count() == 1) {
            $event = $events->first();
            return redirect($event->permalink(), 302);
        }

        // Show a list of all matching events
        return $this->show_events_from_query($events, [
            'year' => $year,
            'month' => $month,
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

        # Single-day event
        if($event->start_date && !$event->start_time && !$event->end_date && !$event->end_time) {
            $start = (new DateTime($event->start_date))->format('Ymd');
        }

        # Multi-day event
        elseif($event->start_date && !$event->start_time && $event->end_date && !$event->end_time) {
            $start = (new DateTime($event->start_date))->format('Ymd');
            $end = (new DateTime($event->end_date))->add(new DateInterval('P1D'))->format('Ymd');
        }

        # Start time but no end time
        elseif($event->start_date && $event->start_time && !$event->end_date && !$event->end_time) {
            $start = (new DateTime($event->start_date.' '.$event->start_time))->format('Ymd\THis');
            # The add to Google link doesn't work with out the end time, so use the start as the end
            $end = $start;
            if($event->timezone) $params['ctz'] = $event->timezone;
        }

        # Start and end time on the same day
        elseif($event->start_date && $event->start_time && !$event->end_date && $event->end_time) {
            $start = (new DateTime($event->start_date.' '.$event->start_time))->format('Ymd\THis');

            if(self::hms_to_sec($event->end_time) < self::hms_to_sec($event->start_time)) {
                $end_date = new DateTime($event->start_date);
                $end_date->add(new DateInterval('P1D'));
                $end_date = $end_date->format('Y-m-d');
            } else {
                $end_date = $event->start_date;
            }

            $end = (new DateTime($end_date.' '.$event->end_time))->format('Ymd\THis');
            if($event->timezone) $params['ctz'] = $event->timezone;
        }

        # Start and end time spanning multiple days
        # TODO: I think this is not possible
        elseif($event->start_date && $event->start_time && $event->end_date && $event->end_time) {
            $start = (new DateTime($event->start_date.' '.$event->start_time))->format('Ymd\THis');
            $end = (new DateTime($event->end_date.' '.$event->end_time))->format('Ymd\THis');
            if($event->timezone) $params['ctz'] = $event->timezone;
        }

        $params['dates'] = $start . '/' . $end;

        $url = 'https://www.google.com/calendar/render?' . http_build_query($params);

        return redirect($url, 302);
    }

    public static function hms_to_sec($hms) {
        $parts = explode(':', $hms);
        return $parts[2] + ($parts[1]*60) + ($parts[0]*60*60);
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

    public function custom_css() {
        $css = Setting::value('custom_global_css');
        return response($css)->header('Content-Type', 'text/css');
    }

    public function manifest_json() {
        $manifest = [
            'name' => env('APP_NAME'),
            'short_name' => env('APP_NAME'),
            'description' => Setting::value('home_meta_description'),
            'icons' => [
                'src' => Setting::value('manifest_logo_url'),
                'sizes' => '192x192',
                'type' => 'image/png',
            ],
            'id' => '/',
            'start_url' => '/',
            'scope' => '/',
            'background_color' => '#fff',
            'theme_color' => '#fff',
            'display' => 'standalone',
        ];
        return response()->json($manifest);
    }
}
