<?php
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Controller as BaseController;
use App\Event, App\Tag, App\Setting;
use App\Helpers\Dates;
use DateTime, DateTimeZone, DateInterval, Exception;
use DB, Auth;

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

        $events = $events->where('unlisted', 0)->where('hide_from_main_feed', 0)->where('is_template', 0)->where('is_proposed', 0);

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
            $query = DB::select('SELECT tag, COUNT(1) AS cities_count, SUM(num) AS events_count
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
                ');
            foreach($query as $tag) {
                // Only show tags used by more than 1 event, otherwise the list is very
                // long and it isn't very interesting to click a tag and see just one event
                if($tag->events_count > 1)
                    $tags[] = $tag;
            }
        }

        $home = (!$year && !$month && !$day);

        return $this->show_events_from_query($events, [
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'tags' => $tags,
            'home' => $home,
            'proposed_count' => ($home && Setting::value('enable_proposed_events')) ? $this->proposed_query()->count() : 0,
        ]);
    }

    // The proposed events that are still looking for a date
    private function proposed_query($include_unlisted=false) {
        $events = Event::where('is_proposed', 1)
            ->where('is_template', 0);

        if(!$include_unlisted)
            $events = $events->where('unlisted', 0);

        return $events;
    }

    public function proposed() {
        if(!Setting::value('enable_proposed_events'))
            abort(404);

        // Unlisted proposals are shown to the people who could have created them
        $events = $this->proposed_query(\Gate::allows('create-event'))
            ->withMin('date_options', 'date')
            ->with('date_options', 'tags')
            ->orderBy('date_options_min_date')
            ->orderBy('created_at')
            ->get();

        return view('proposed', [
            'events' => $events,
            'page_title' => __('events.proposed.title'),
        ]);
    }

    public function proposed_event($key_or_slug, $key2=false) {
        if($key2) {
            $key = $key2;
            $slug = $key_or_slug;
        } else {
            $key = $key_or_slug;
            $slug = false;
        }

        $event = Event::where('key', $key)->first();

        if(!$event || $event->is_template) {
            abort(404);
        }

        // Once a date is chosen the event lives at its dated URL for good. An event that
        // is neither proposed nor dated has no page, and redirecting it would loop.
        if(!$event->is_proposed) {
            if(!$event->start_date) {
                abort(404);
            }
            return redirect($event->permalink(), 301);
        }

        if($event->slug && $event->slug != $slug) {
            return redirect($event->permalink(), 301);
        }

        $leading = $event->leading_date_option();

        return view('event', [
            'event' => $event,
            'year' => null,
            'month' => null,
            'key' => $key,
            'slug' => $slug,
            'mode' => 'event',
            'page_title' => $event->name . ' | ' . __('events.proposed.page_title'),
            'date_options' => $event->date_options_with_tallies(),
            'leading_option_id' => $leading ? $leading->id : null,
            'user_votes' => Auth::user() ? $event->date_votes_for_user(Auth::user()) : [],
        ]);
    }

    public function tag($tag) {
        $tags = [];
        foreach(explode(',', $tag) as $t) {
            $tags[] = Tag::lookup($t);
        }

        $year = $month = false;

        if(request('year') && is_numeric(request('year'))) {
            $year = request('year');
            if(request('month') && is_numeric(request('month')))
                $month = request('month');
        }

        $events = $this->events_query($year, $month);

        $events = Event::tagged($events, $tags);

        $events = $events->get();

        if(count($events) <= 1) {
            $now = new DateTime('now', new DateTimeZone('+12:00')); // opposite offset compared to looking for future events
            $nowDate = $now->format('Y-m-d');

            $past_events = $this->events_query(false, false, false, false);

            $past_events = $past_events->whereHas('tags', function($query) use ($tags){
                $query->whereIn('tag', $tags);
            })->where(function($query)use($nowDate){
                $query->where('start_date', '<=', $nowDate)
                      ->orWhere('end_date', '<=', $nowDate);
            })->limit(6);

            $past_events = $past_events->get();
        } else {
            $past_events = [];
        }

        return $this->show_events_from_query($events, [
            'tags' => $tags,
            'past_events' => $past_events,
            'page_title' => '',
            'page_type' => 'tag',
        ]);
    }

    public function tag_archive($tag) {
        $tags = [];
        foreach(explode(',', $tag) as $t) {
            $tags[] = Tag::lookup($t);
        }

        $query = Event::tagged($this->events_query(false, false, false, false), $tags)
            ->orderBy('id', 'desc');

        $page = $this->cursor_page((clone $query)->with(['parent', 'tags']), self::TAG_ARCHIVE_PER_PAGE);
        $events = $page['events'];

        // A tag with no events at all has no archive
        if($events->isEmpty()) {
            abort(404);
        }

        return $this->show_events_from_query($events, [
            'tags' => $tags,
            'archive' => true,
            'page_title' => '',
            'page_type' => 'tag',
            'page' => $page,
            'years' => $this->year_index($query),
            'month_format' => 'month_year',
            'on_tag_archive' => true,
        ]);
    }

    public function year_tag($year, $tag) {
        $tags = [];
        foreach(explode(',', $tag) as $t) {
            $tags[] = Tag::lookup($t);
        }

        $now = new DateTime('now', new DateTimeZone('-12:00'));
        $nowDate = $now->format('Y-m-d');

        $upcoming_events = $this->events_query($year);
        $upcoming_events = $upcoming_events->where(function($query)use($nowDate){
            $query->where('start_date', '>=', $nowDate)
                  ->orWhere('end_date', '>=', $nowDate);
        });

        $upcoming_events = Event::tagged($upcoming_events, $tags);

        $upcoming_event_ids = $upcoming_events->pluck('id');
        $upcoming_events = $upcoming_events->get();

        $past_events = $this->events_query($year, false, false, false);
        $past_events = $past_events->whereRaw('
            ((start_date < "'.$nowDate.'" AND end_date IS NULL)
            OR
            (end_date < "'.$nowDate.'"))
        ');
        $past_events = Event::tagged($past_events, $tags);
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
            'page_title' => __('events.title.year', ['year' => $year]),
            'tags' => $tags,
            'page_type' => 'tag',
        ]);
    }

    const ARCHIVE_PER_PAGE = 50;
    // The tag archive shows each event in full, so its pages are shorter
    const TAG_ARCHIVE_PER_PAGE = 20;

    // Groups events into [year][month][] with integer keys, for the month-by-month lists
    private function group_by_year_month($events) {
        $data = [];
        foreach($events as $event) {
            $ts = strtotime($event->start_date);
            $data[(int)date('Y', $ts)][(int)date('n', $ts)][] = $event;
        }
        return $data;
    }

    // How many events each year has, newest year first, for the index at the top of an archive
    private function year_index(Builder $query) {
        return (clone $query)
            ->reorder()
            ->selectRaw('YEAR(start_date) AS year, COUNT(*) AS count')
            ->groupByRaw('YEAR(start_date)')
            ->orderByRaw('YEAR(start_date) DESC')
            ->toBase()
            ->pluck('count', 'year');
    }

    /**
     * A page of an archive: up to $limit events, newest first.
     *
     * Pages are addressed by cursors rather than numbers. ?before=<start>,<id> shows the events
     * older than that event and ?after=<start>,<id> the ones newer, so a page's URL keeps
     * showing the same events after new ones are added. The query must already be ordered by
     * sort_date and then id, both descending.
     *
     * Returns ['events' => Collection, 'newer' => URL or null, 'older' => URL or null].
     */
    private function cursor_page(Builder $query, $limit = self::ARCHIVE_PER_PAGE) {
        $before = $this->parse_cursor(request('before'));
        $after = $this->parse_cursor(request('after'));

        if($after) {
            // The events just after the cursor, found in the other direction and turned around
            $rows = (clone $query)->reorder()
                ->where(function($q) use($after){ $this->newer_than($q, $after); })
                ->orderBy('sort_date')->orderBy('id')
                ->limit($limit + 1)->get();
            $events = $rows->take($limit)->reverse()->values();
        } else {
            $page_query = clone $query;
            if($before)
                $page_query->where(function($q) use($before){ $this->older_than($q, $before); });
            $rows = $page_query->limit($limit + 1)->get();
            $events = $rows->take($limit)->values();
        }

        // A cursor that nothing comes before or after is a page that doesn't exist
        if($events->isEmpty() && ($before || $after))
            abort(404);

        $older = null;
        $newer = null;

        if($events->isNotEmpty()) {
            // Coming from a newer cursor there is always something older: the cursor event itself
            if($after || $rows->count() > $limit)
                $older = request()->url().'?before='.$this->event_cursor($events->last());

            if($before || $after) {
                $first = $events->first();
                $newer_count = (clone $query)->reorder()
                    ->where(function($q) use($first){ $this->newer_than($q, $this->cursor_of($first)); })
                    ->limit($limit + 1)->pluck('id')->count();

                // The previous page is the first page unless more than a page's worth is newer
                if($newer_count > $limit)
                    $newer = request()->url().'?after='.$this->event_cursor($first);
                elseif($newer_count > 0)
                    $newer = request()->url();
            }
        }

        return ['events' => $events, 'newer' => $newer, 'older' => $older];
    }

    // The cursor naming an event: its start in UTC and its id, like 2025-11-20T18:00:00Z,1234
    private function event_cursor(Event $event) {
        $cursor = $this->cursor_of($event);
        return (new DateTime($cursor['sort_date'], new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z').','.$cursor['id'];
    }

    private function cursor_of(Event $event) {
        return ['sort_date' => \App\DiscordNotification::eventStart($event), 'id' => (int)$event->id];
    }

    // Reads a cursor from the query string. Anything that isn't one is a page that doesn't exist.
    private function parse_cursor($value) {
        if($value === null || $value === '')
            return null;

        if(!is_string($value) || !preg_match('/^(\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ),(\d+)$/', $value, $match))
            abort(404);

        $date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $match[1], new DateTimeZone('UTC'));
        if(!$date)
            abort(404);

        return ['sort_date' => $date->format('Y-m-d H:i:s'), 'id' => (int)$match[2]];
    }

    private function older_than($q, array $cursor) {
        $q->where('sort_date', '<', $cursor['sort_date'])
          ->orWhere(function($q) use($cursor){
              $q->where('sort_date', $cursor['sort_date'])->where('id', '<', $cursor['id']);
          });
    }

    private function newer_than($q, array $cursor) {
        $q->where('sort_date', '>', $cursor['sort_date'])
          ->orWhere(function($q) use($cursor){
              $q->where('sort_date', $cursor['sort_date'])->where('id', '>', $cursor['id']);
          });
    }

    private function show_events_from_query(&$events, $opts=[]) {
        $data = $this->group_by_year_month($events);

        if(isset($opts['past_events'])) {
            $opts['past_events'] = $this->group_by_year_month($opts['past_events']);
        }

        if(!isset($opts['page_title'])) {
            if(!empty($opts['day'])) {
                $opts['page_title'] = __('events.title.day', ['site' => env('APP_NAME'), 'date' => Dates::format($opts['year'].'-'.$opts['month'].'-'.$opts['day'], 'date_long')]);
            } elseif(!empty($opts['month'])) {
                $opts['page_title'] = __('events.title.month', ['site' => env('APP_NAME'), 'month' => Dates::format($opts['year'].'-'.$opts['month'].'-01', 'month_year')]);
            } elseif(!empty($opts['year'])) {
                $opts['page_title'] = __('events.title.year_on_site', ['site' => env('APP_NAME'), 'year' => $opts['year']]);
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

        $query = Event::where('start_date', '<', $now->format('Y-m-d'))
            ->where('unlisted', 0)
            ->where('is_template', 0)
            ->where('is_proposed', 0)
            ->orderBy('sort_date', 'desc')
            ->orderBy('id', 'desc');

        $years = $this->year_index($query);
        $page = $this->cursor_page($query->with('parent'));

        return view('archive', [
            'page_title' => __('events.past_events'),
            'data' => $this->group_by_year_month($page['events']),
            'page' => $page,
            'years' => $years,
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
        $query = DB::select('SELECT tag, COUNT(1) AS num_cities, SUM(num) AS num_events
            FROM
            (SELECT tags.tag, events.location_locality AS locality, COUNT(1) AS num
            FROM events
            JOIN event_tag ON event_tag.event_id = events.id
            JOIN tags ON event_tag.tag_id = tags.id
            WHERE unlisted = 0 AND is_template = 0 AND is_proposed = 0
            GROUP BY tag, locality
            ORDER BY tag) AS data
            GROUP BY tag
            ORDER BY num_cities DESC, tag
            ');

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

        if($event->is_template) {
            abort(404);
        }

        // A proposed event has its own URL until a date is chosen, so this is temporary
        if($event->is_proposed || !$event->start_date) {
            return redirect($event->permalink(), 302);
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
        $event = Event::where('key', $key)->where('is_template', 0)->where('is_proposed', 0)->first();
        if(!$event) {
            abort(404);
        }

        $meeting_url = false;

        if($event->meeting_url_is_visible()) {
            $meeting_url = $event->meeting_url;
        }

        return response()->json([
            'meeting_url' => $meeting_url,
            'meeting_state' => $event->meeting_state(),
        ]);
    }

    public function export_event_json(Event $event, $secretkey) {
        if(!$event->export_secret || !hash_equals((string)$event->export_secret, (string)$secretkey)) {
            abort(403);
        }

        $data = [
            'generator' => 'Meetable',
            'version' => '1.0',
            'event' => $event,
        ];

        // The candidate dates and how the voting stands, without naming the voters
        if($event->date_options->count()) {
            $data['date_options'] = $event->date_options_with_tallies()->map(function($option){
                return [
                    'id' => $option->id,
                    'date' => $option->date,
                    'end_date' => $option->end_date,
                    'start_time' => $option->start_time,
                    'end_time' => $option->end_time,
                    'votes' => $option->vote_counts(),
                ];
            })->values();
        }

        return response()->json($data);
    }

    public function event_shorturl($key) {
        return $this->event(0, 0, $key);
    }

    public function find_matching_events($year, $month, $partial_slug) {
        // Match the beginning of the slug literally, so % and _ can't list every event in the month
        $prefix = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $partial_slug);

        $events = Event::whereYear('start_date', $year)
          ->whereMonth('start_date', $month)
          ->where('slug', 'like', $prefix.'%')
          ->where('unlisted', 0)
          ->where('is_template', 0)
          ->where('is_proposed', 0)
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
        $event = Event::where('key', $key)->where('is_template', 0)->where('is_proposed', 0)->first();

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

    // Remembers the language a visitor picked, instead of the one their browser asks for
    public function set_language($locale) {
        if(!in_array($locale, \App\Helpers\Locales::available()))
            abort(404);

        return redirect(\App\Helpers\Uri::same_origin_path(request()->headers->get('referer')))
            ->withCookie(cookie()->forever(\App\Http\Middleware\SetLocale::COOKIE, $locale));
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
