<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DateTime, DateTimeZone, DateInterval, DatePeriod;
use DB, Str, Log;
use App\Services\Zoom;

class Event extends Model
{
    use SoftDeletes;

    protected $hidden = [
        'id', 'export_secret', 'created_by', 'last_modified_by', 'tags',
    ];

    protected $appends = [
        'tag_list',
    ];

    private static $US_NAMES = ['US', 'USA', 'United States'];

    public static $STATUSES = [
        'confirmed' => 'Confirmed',
        'tentative' => 'Tentative',
        'postponed' => 'Postponed',
        'cancelled' => 'Cancelled',
    ];

    public static $EDITABLE_PROPERTIES = [
        'name', 'start_date', 'end_date', 'start_time', 'end_time',
        'location_name', 'location_address', 'location_locality', 'location_region', 'location_country',
        'latitude', 'longitude', 'timezone', 'status',
        'website', 'tickets_url', 'code_of_conduct_url', 'meeting_url', 'video_url', 'notes_url',
        'summary', 'description', 'cover_image', 'unlisted', 'parent_id', 'hide_from_main_feed',
        'recurrence_interval',
    ];

    public static function slug_from_name($name) {
        return preg_replace('/--+/', '-', mb_ereg_replace('[^a-z0-9à-öø-ÿāăąćĉċčŏœ]+', '-', mb_strtolower($name)));
    }

    public static function find_from_url($url) {
        // /{year}/{month}/{slug}-{key}
        if(preg_match('~/[0-9]{4}/[0-9]{2}/(.+-)?([0-9a-zA-Z]{12})$~', $url, $match)) {
            return Event::where('key', $match[2] ?? $match[1])->first();
        } elseif(preg_match('~^/([0-9a-zA-Z]{12})$~', $url, $match)) {
            return Event::where('key', $match[1])->first();
        } else {
            return null;
        }
    }

    public function responses() {
        return $this->hasMany('\App\Response')
            ->where('approved', true)
            ->orderBy('created_at', 'desc');
    }

    public function pending_responses() {
        return $this->hasMany('\App\Response')
            ->where('approved', false)
            ->orderBy('created_at', 'desc');
    }

    public function num_pending_responses() {
        return $this->pending_responses
            ->count();
    }

    public function revisions() {
        return $this->hasMany('\App\EventRevision')
            ->orderBy('created_at', 'desc');
    }

    public function photos() {
        return $this->hasManyThrough('\App\ResponsePhoto', '\App\Response')
            ->where('approved', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('response_photos.created_at', 'desc');
    }

    public function rsvp_for_user(User $user) {
        return $this->rsvps()->where('rsvp_user_id', $user->id)->first();
    }

    public function rsvp_string_for_user(User $user) {
        $rsvp = $this->rsvp_for_user($user);
        return $rsvp ? $rsvp->rsvp : null;
    }

    public function createdBy() {
        return $this->belongsTo('\App\User', 'created_by');
    }

    public function lastModifiedBy() {
        return $this->belongsTo('\App\User', 'last_modified_by');
    }

    public function tags() {
        return $this->belongsToMany('\App\Tag');
    }

    public function getTagListAttribute() {
        return array_map(function($t){ return $t->tag; }, $this->tags->all());
    }

    // Used when building a query to find events with a matching tag
    public static function tagged(\Illuminate\Database\Eloquent\Builder $queryBuilder, $tags) {
        return $queryBuilder->whereHas('tags', function($query) use ($tags){
            $query->whereIn('tag', array_map(function($t){ return $t->tag; }, $tags));
        });
    }

    public $temp_tag_string;

    public function tags_string() {
        if($this->temp_tag_string)
            return $this->temp_tag_string;

        $tags = [];
        foreach($this->tags as $t)
            $tags[] = $t->tag;
        return implode(' ', $tags);
    }

    public function parent() {
        return $this->belongsTo('\App\Event', 'parent_id');
    }

    public function children() {
        return $this->hasMany('\App\Event', 'parent_id')
            ->orderBy('sort_date', 'asc');
    }

    public function has_rsvps() {
        return $this->rsvps->count();
    }

    public function has_photos() {
        return $this->photos->count();
    }

    public function has_blog_posts() {
        return $this->blog_posts->count();
    }

    public function has_comments() {
        return $this->comments->count();
    }

    public function has_likes() {
        return $this->likes->count();
    }

    public function rsvps() {
        return $this->responses()->whereIn('rsvp', ['yes','no','maybe','remote'])->orderBy('created_at', 'desc');
    }

    public function rsvps_yes() {
        return $this->responses()->where('rsvp', 'yes')->orderBy('created_at', 'desc');
    }

    public function rsvps_no() {
        return $this->responses()->where('rsvp', 'no')->orderBy('created_at', 'desc');
    }

    public function rsvps_maybe() {
        return $this->responses()->where('rsvp', 'maybe')->orderBy('created_at', 'desc');
    }

    public function rsvps_remote() {
        return $this->responses()->where('rsvp', 'remote')->orderBy('created_at', 'desc');
    }

    public function blog_posts() {
        return $this->responses()->whereNotNull('name')->orderBy('created_at', 'desc');
    }

    public function comments() {
        return $this->responses()->whereNull('name')->whereNotNull('content_text')->orderBy('created_at', 'desc');
    }

    public function likes() {
        return $this->responses()->where('is_like', 1)->orderBy('created_at', 'asc');
    }

    public function generate_random_values() {
        $this->key = Str::random(12);
        $this->export_secret = Str::random(20);
    }

    public function permalink() {
        $date = new DateTime($this->start_date);
        return '/' . $date->format('Y') . '/' . $date->format('m') . '/' . ($this->slug ? $this->slug.'-' : '') . $this->key;
    }

    public function ics_permalink() {
        return '/ics' . $this->permalink() . '.ics';
    }

    public function tag_feed_ics_link() {
        $tag = $this->tags[0]->tag;
        return route('ics-tag-preview', $tag);
    }

    public function absolute_permalink() {
        return env('APP_URL').$this->permalink();
    }

    public function shortlink() {
        return '/' . $this->key;
    }

    public function absolute_shortlink() {
        return env('APP_URL').$this->shortlink();
    }

    public function code_of_conduct_urls() {
        if(!$this->code_of_conduct_url) return [];

        return explode(' ', $this->code_of_conduct_url);
    }

    public function sort_date() {
        if($this->timezone) {
            $tz = new DateTimeZone($this->timezone);
        } else {
            // Events without a timezone will be sorted as if they were in UTC
            $tz = new DateTimeZone('UTC');
        }
        if($this->start_time) {
            $sort_date = new DateTime($this->start_date.' '.$this->start_time, $tz);
        } else {
            $sort_date = new DateTime($this->start_date, $tz);
        }
        $sort_date = $sort_date->setTimeZone(new DateTimeZone('UTC'));
        return $sort_date;
    }

    public function is_multiday() {
        return $this->end_date && $this->end_date != $this->start_date;
    }

    public function date_summary() {
        $start_date = new DateTime($this->start_date);

        if($this->is_multiday()) {
            $end_date = new DateTime($this->end_date);

            if($start_date->format('Y') != $end_date->format('Y')) {
                $start_text = $start_date->format('M j, Y');
                $end_text = $end_date->format('M j, Y');
            } elseif($start_date->format('F') == $end_date->format('F')) {
                $start_text = $start_date->format('M j');
                $end_text = $end_date->format('j, Y');
            } else {
                $start_text = $start_date->format('M j');
                $end_text = $end_date->format('M j, Y');
            }

            return '<time datetime="'.$start_date->format('Y-m-d').'">'
                    . $start_text
                    . '</time> - '
                    . '<time datetime="'.$end_date->format('Y-m-d').'">'
                    . $end_text
                    . '</time>';

        } else {
            if($this->start_time) {
                $start = $this->start_datetime();
                if($this->timezone) {
                    $tzattrs = 'class="has-tooltip-bottom event-localize-date '.(!$this->has_physical_location() ? 'is-virtual-event' : '').'" data-timezone="'.$this->timezone.'" data-original-date="'.$start->format('M j, Y g:ia').'" data-dateformat="full"';
                } else {
                    $tzattrs = '';
                }
                return '<time datetime="'.$start->format('c').'" '.$tzattrs.'>'
                        . $start->format('M j, Y').' '.$start->format('g:ia')
                        . ($this->has_physical_location() ? ' ('.$start->format('P').')' : '')
                        . '</time>';
            } else {
                return '<time datetime="'.$start_date->format('Y-m-d').'">'
                        . $start_date->format('M j, Y')
                        . '</time>';
            }
        }
    }

    public function date_summary_text() {
        return strip_tags($this->date_summary());
    }

    public function start_datetime_local($format='Ymd\THi') {
        $start_date = new DateTime($this->start_date.' '.$this->start_time);
        return $start_date->format($format);
    }

    public function start_datetime() {
        if($this->timezone) {
            $tz = new DateTimeZone($this->timezone);
            $start = new DateTime($this->start_date.' '.$this->start_time, $tz);
        } else {
            $start = new DateTime($this->start_date.' '.$this->start_time);
        }
        return $start;
    }

    public function end_datetime() {
        if(!$this->end_time)
            return null;

        $date = $this->start_datetime();

        if(self::hms_to_sec($this->end_time) < self::hms_to_sec($this->start_time)) {
            $date->add(DateInterval::createFromDateString('1 day'));
        }


        if($this->timezone) {
            $tz = new DateTimeZone($this->timezone);
            $end = new DateTime($date->format('Y-m-d ').$this->end_time, $tz);
        } else {
            $end = new DateTime($date->format('Y-m-d ').$this->end_time);
        }

        return $end;
    }

    public function display_date() {
        $start_date = new DateTime($this->start_date);

        if($this->is_multiday()) {
            $end_date = new DateTime($this->end_date);

            if($start_date->format('Y') != $end_date->format('Y')) {
                return $start_date->format('F j, Y') . ' - ' . $end_date->format('F j, Y');
            } elseif($start_date->format('F') == $end_date->format('F')) {
                return $start_date->format('F j') . ' - ' . $end_date->format('j, Y');
            } else {
                return $start_date->format('F j') . ' - ' . $end_date->format('F j, Y');
            }

        } else {
            return $start_date->format('F j, Y');
        }
    }

    public function display_time() {
        if(!$this->start_time)
            return '';

        $start_time = new DateTime($this->start_time);

        if($this->end_time) {
            $end_time = new DateTime($this->end_time);
            if($start_time->format('a') == $end_time->format('a'))
                $start_format = 'g:i';
            else
                $start_format = 'g:ia';
            $str = $start_time->format($start_format) . ' - ' . $end_time->format('g:ia');
        } else {
            $str = $start_time->format('g:ia');
        }

        return $str;
    }

    public function duration_minutes() {
        list($start, $end) = $this->start_and_end_dates();
        if(!$end)
            return null;

        return (strtotime($end) - strtotime($start)) / 60;
    }

    public function weekday() {
        $start_date = new DateTime($this->start_date);
        return $start_date->format('D');
    }

    public function start_and_end_dates() {
        $start = false;
        $end = false;

        if($this->is_multiday()) {
            $start = (new DateTime($this->start_date))->format('Y-m-d');
            $end = (new DateTime($this->end_date))->format('Y-m-d');
        } else {

            if($this->start_date && $this->start_time && $this->end_time) {
                $start = $this->start_datetime()->format('Y-m-d\TH:i:sP');
                $end = $this->end_datetime()->format('Y-m-d\TH:i:sP');
            }
            elseif($this->start_date && $this->start_time && !$this->end_time) {
                $start = $this->start_datetime()->format('Y-m-d\TH:i:sP');
            }
            elseif($this->start_date && !$this->start_time && !$this->end_time) {
                $start = (new DateTime($this->start_date))->format('Y-m-d');
            }

        }

        return [$start, $end];
    }

    public function mf2_date_html() {
        list($start, $end) = $this->start_and_end_dates();

        $start_html = '<data class="dt-start" value="' . $start . '"></data>';
        $end_html = $end ? '<data class="dt-end" value="' . $end . '"></data>' : '';

        return $start_html . $end_html;
    }

    public function recurrence_description() {
        if(!$this->recurrence_interval)
            return '';

        $start = new DateTime($this->start_date);

        switch($this->recurrence_interval) {
            case 'weekly_dow':
                return 'Every week on '.$start->format('l').'s';
            case 'biweekly_dow':
                return 'Every other week on '.$start->format('l').'s';
            case 'monthly_date':
                return 'Every month on the '.$start->format('dS');
            case 'yearly':
                return 'Every year on '.$start->format('d');
        }
    }

    public function recurrence_date_interval() {
        if(!$this->recurrence_interval)
            return null;

        switch($this->recurrence_interval) {
            case 'weekly_dow':
                return new DateInterval('P1W');
            case 'biweekly_dow':
                return new DateInterval('P2W');
            case 'monthly_date':
                return new DateInterval('P1M');
            case 'yearly':
                return new DateInterval('P1Y');
        }
    }

    public function recurrence_end_datetime() {
        if(!$this->recurrence_interval)
            return null;

        $now = new DateTime();

        switch($this->recurrence_interval) {
            case 'weekly_dow':
                return $now->add(new DateInterval('P5W'));
            case 'biweekly_dow':
                return $now->add(new DateInterval('P9W'));
            case 'monthly_date':
                return $now->add(new DateInterval('P4M'));
            case 'yearly':
                return $now->add(new DateInterval('P2Y'));
        }
    }

    public function create_upcoming_recurrences() {
        // Find the next events to schedule out over the next N weeks
        $interval = $this->recurrence_date_interval();
        $start = $this->start_datetime();
        $now = new DateTime();

        Log::info($this->name);
        Log::info('Series starts: '.$start->format('Y-m-d'));
        Log::info('Recurrence: '.$this->recurrence_interval);

        $end = $this->recurrence_end_datetime();

        Log::info('Today: '.$now->format('Y-m-d'));
        Log::info('Target end date: '.$end->format('Y-m-d'));

        $period = new DatePeriod($start, $interval, $end);

        foreach($period as $date) {
            if($date >= $now) {
                $exists = Event::where('created_from_template_event_id', $this->id)
                  ->where('start_date', $date->format('Y-m-d'))
                  ->count();
                if($exists == 0) {
                    Log::info('  Creating instance on '.$date->format('Y-m-d'));

                    $copy = $this->replicate();
                    $copy->generate_random_values();
                    $copy->created_from_template_event_id = $this->id;
                    $copy->start_date = $date->format('Y-m-d');
                    $copy->is_template = false;
                    $copy->recurrence_interval = null;
                    $copy->sort_date = $copy->sort_date();
                    $copy->reset_live_event_stats();

                    // Replace any YYYY-mm-dd dates in the description or URL properties
                    $copy->replace_date($this, 'description');
                    $copy->replace_date($this, 'notes_url');
                    $copy->replace_date($this, 'website');

                    $copy->save();

                    if($this->tags()->count() > 0) {
                        foreach($this->tags as $tag) {
                            $copy->tags()->attach($tag);
                        }
                    }
                }
            }
        }
    }

    public function delete_upcoming_recurrences() {
        $date = new DateTime();
        Event::where('created_from_template_event_id', $this->id)
            ->where('start_date', '>', $date->format('Y-m-d'))
            ->delete();
    }

    private function replace_date($template_event, $property) {
        $this->{$property} = str_replace($template_event->start_datetime()->format('Y-m-d'), $this->start_datetime()->format('Y-m-d'), $template_event->{$property});
    }

    public function reset_live_event_stats() {
        $this->current_participants = 0;
        $this->max_participants = 0;
        $this->notification_sent = 0;
    }

    public function is_starting_soon() {
        if($this->is_past() || $this->status != 'confirmed')
            return false;

        // Return true if the event is starting in 15 minutes or less
        if($this->timezone) {
            $tz = new DateTimeZone($this->timezone);
        } else {
            // Fall back to earliest timezone, not ideal, but we shouldn't be creating zoom
            // meetings for events without a timezone anyway
            $tz = new DateTimeZone('-12:00');
        }

        // Events using this should also usually have a start time set
        $date = new DateTime($this->start_date.' '.$this->start_time, $tz);

        $now = new DateTime();

        return $now->format('U') > ($date->format('U') - 60*15);
    }

    public function is_ongoing() {
        if($this->is_past() || $this->status != 'confirmed')
            return false;

        // If Zoom has sent the meeting started notification early, return true now
        if($this->zoom_meeting_status == 'started')
            return true;

        // Return true if it is past the start time of the event
        if($this->timezone) {
            $tz = new DateTimeZone($this->timezone);
        } else {
            // Fall back to earliest timezone, not ideal, but we shouldn't be creating zoom
            // meetings for events without a timezone anyway
            $tz = new DateTimeZone('-12:00');
        }

        // Events using this should also usually have a start time set
        $date = new DateTime($this->start_date.' '.$this->start_time, $tz);

        $now = new DateTime();

        return $now->format('U') > $date->format('U');
    }

    public function is_past($now=null) {
        // Always report the event is over if Zoom has sent the meeting ended notification
        if($this->zoom_meeting_status == 'ended')
            return true;

        if($this->timezone) {
            $tz = new DateTimeZone($this->timezone);
        } else {
            // Show "I'm going" unless the event is for sure past.
            // Fall back to last timezone.
            $tz = new DateTimeZone('-12:00');
        }

        if($this->end_date) {
            $date = new DateTime($this->end_date.' 23:59', $tz);
        } else {
            if($this->end_time) {
                $date = $this->end_datetime();
            } else {
                $date = $this->start_datetime();
                // Add 30 minute padding if the event has no end time. Treats events as default 30 minutes.
                $date->add(DateInterval::createFromDateString('30 minutes'));
            }
        }

        if(!$now)
            $now = new DateTime();

        // If there is a zoom meeting ID, we expect the event to only be over once the status is ended.
        // If the status is "started", then don't say the meeting is over even if the end time is reached.
        if($this->zoom_meeting_id) {
            // If for some reason the webhook failed, we need to eventually stop saying it's still going.
            // If it has been more than 12h after the event end, it is over.
            $fallback = clone $date;
            $fallback->add(DateInterval::createFromDateString('12 hours'));
            if($now->format('U') > $fallback->format('U'))
                return true;

            if($this->zoom_meeting_status == 'started')
                return false;
        }

        return $date->format('U') < $now->format('U');
    }

    public function status_tag() {
        // Live now
        if($this->meeting_url && $this->is_ongoing()) {
            $icon = 'play-circle';
            $class = 'success';
            $text = 'Live Now';
        } else if($this->status == 'confirmed') {
            return '';
        }

        switch($this->status) {
            case 'cancelled':
              $icon = 'exclamation-triangle';
              $class = 'danger';
              $text = 'Cancelled';
              break;
            case 'postponed':
              $icon = 'question-circle';
              $class = 'warning';
              $text = 'Postponed';
              break;
            case 'tentative':
              $icon = 'question-circle';
              $class = 'warning';
              $text = 'Tentative';
              break;
        }

        return '<span class="status tag is-'.$class.'">'
            .'<svg class="svg-icon" style="margin-right:5px;"><use xlink:href="/font-awesome-5.11.2/sprites/solid.svg#'.$icon.'"></use></svg>'
            .substr(strtoupper($text), 0, 1)
            .'<span class="lower">'.substr(strtoupper($text), 1).'</span>'
            .'<span class="hidden">:</span>'
            .'</span> ';
    }

    public function status_text() {
        if($this->status == 'confirmed')
            return '';

        return strtoupper($this->status).': ';
    }

    public function location_summary() {
        $str = [];
        if($this->location_address) $str[] = $this->location_address;
        if($this->location_locality) $str[] = $this->location_locality;
        if($this->location_region) $str[] = $this->location_region;
        if($this->location_country && !in_array($this->location_country, self::$US_NAMES)) $str[] = $this->location_country;
        return implode(', ', $str);
    }

    public function location_summary_with_mf2() {
        $str = [];
        if($this->location_address) $str[] = '<span class="p-street-address">'.e($this->location_address).'</span>';
        if($this->location_locality) $str[] = '<span class="p-locality">'.e($this->location_locality).'</span>';
        if($this->location_region) $str[] ='<span class="p-region">'.e($this->location_region).'</span>';
        $country_data = '';
        if($this->location_country) {
            if(in_array($this->location_country, self::$US_NAMES)) {
                $country_data = '<data class="p-country-name" value="'.e($this->location_country).'"></data>';
            } else {
                $str[] = '<span class="p-country-name">'.e($this->location_country).'</span>';
            }
        }
        return implode(', ', $str) . $country_data;
    }

    public function location_summary_with_name() {
        $str = [];
        if($this->location_name) $str[] = $this->location_name;
        if($this->location_address) $str[] = $this->location_address;
        if($this->location_locality) $str[] = $this->location_locality;
        if($this->location_region) $str[] = $this->location_region;
        if($this->location_country && !in_array($this->location_country, self::$US_NAMES))
            $str[] = $this->location_country;
        return implode(', ', $str);
    }

    public function location_city() {
        $str = [];
        if($this->location_locality) $str[] = $this->location_locality;
        if(in_array($this->location_country, self::$US_NAMES)) {
            if($this->location_region) $str[] = $this->location_region;
        } else {
            if($this->location_country) $str[] = $this->location_country;
            elseif($this->location_region) $str[] = $this->location_region;
        }
        return implode(', ', $str);
    }

    public function has_physical_location() {
        return $this->location_address || $this->location_locality || $this->location_region || $this->location_country || $this->latitude;
    }

    public function html() {
        if(!$this->description)
            return '';

        return $this->sanitized_html($this->description);
    }

    public function summary_html() {
        if(!$this->summary)
            return '';

        return $this->sanitized_html($this->summary);
    }

    private function sanitized_html($html) {
        $html = \Michelf\MarkdownExtra::defaultTransform($html);

        $html = \p3k\HTML::sanitize($html, ['allowTables' => true]);
        // Add Bulma css for tables
        $html = preg_replace('/^<table>$/m', '<table class="table is-fullwidth is-bordered">', $html);

        return $html;
    }

    private static $YOUTUBE_VIDEOID_REGEX = '/(?:v=|\.be\/)([a-zA-Z0-9_-]+)/';
    private static $IA_VIDEOSLUG_REGEX = '/(?:details\/)([a-zA-Z0-9_-]+)/';

    public function can_embed_video() {
        if(!$this->video_url)
            return false;

        $host = parse_url($this->video_url, PHP_URL_HOST);

        if(in_array($host, ['www.youtube.com', 'youtube.com', 'youtu.be'])) {
            if(preg_match(self::$YOUTUBE_VIDEOID_REGEX, $this->video_url, $match))
                return true;
        }

        if(in_array($host, ['archive.org'])) {
            if(preg_match(self::$IA_VIDEOSLUG_REGEX, $this->video_url, $match))
                return true;
        }

        return false;
    }

    public function video_embed_html() {
        if(!$this->video_url)
            return '';

        $host = parse_url($this->video_url, PHP_URL_HOST);

        switch($host) {
            case 'www.youtube.com':
            case 'youtube.com':
            case 'youtu.be':
                if(!preg_match(self::$YOUTUBE_VIDEOID_REGEX, $this->video_url, $match))
                    return '';

                $videoID = $match[1];

                return '<iframe width="100%" height="420" src="https://www.youtube-nocookie.com/embed/'.$videoID.'" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';

            case 'archive.org':
                if(!preg_match(self::$IA_VIDEOSLUG_REGEX, $this->video_url, $match))
                    return '';

                $videoSlug = $match[1];

                return '<iframe src="https://archive.org/embed/'.$videoSlug.'" width="100%" height="420" frameborder="0" webkitallowfullscreen="true" mozallowfullscreen="true" allowfullscreen></iframe>';
        }
    }

    public function setTicketsUrlAttribute($value) {
        $this->attributes['tickets_url'] = $value ?: null;
        // Disable RSVPs if there is a ticket URL set
        $this->attributes['rsvps_enabled'] = $value ? false : true;
    }

    public function setLatitudeAttribute($value) {
        $this->attributes['latitude'] = $value ?: null;
    }

    public function setLongitudeAttribute($value) {
        $this->attributes['longitude'] = $value ?: null;
    }

    public function setTimezoneAttribute($value) {
        $this->attributes['timezone'] = $value ?: null;
    }

    public function toGoogleJSON() {
        // https://developers.google.com/search/docs/data-types/event

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $this->name,
            'location' => [
                '@type' => 'Place',
                'name' => $this->location_name,
                'address' => [
                    '@type' => 'PostalAddress',
                ],
            ],
        ];

        if($this->location_address)
            $data['location']['address']['streetAddress'] = $this->location_address;

        if($this->location_locality)
            $data['location']['address']['addressLocality'] = $this->location_locality;

        if($this->location_region)
            $data['location']['address']['addressRegion'] = $this->location_region;

        if($this->location_country)
            $data['location']['address']['addressCountry'] = $this->location_country;

        // Remove the location property if no values were added
        if(count($data['location']['address']) == 1) {
            unset($data['location']);
        }

        if($this->description)
            $data['description'] = substr($this->description, 0, 512).'...'; // google only shows a snippet

        list($start, $end) = $this->start_and_end_dates();

        $data['startDate'] = $start;
        if($end)
            $data['endDate'] = $end;

        $data['image'] = $this->cover_image_absolute_url();

        if($this->tickets_url) {
            $data['offers'] = [
                '@type' => 'Offer',
                'url' => $this->tickets_url,
            ];
        } elseif($this->website) {
            $data['offers'] = [
                '@type' => 'Offer',
                'url' => $this->website,
            ];
        }

        return json_encode($data, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
    }

    public function cover_image_absolute_url() {
        if($this->cover_image) {
            // Return the absolute URL for the cover image, which is required by Twitter/FB
            // If the URL does not already start with http, assume it's stored locally and add the app URL
            if(!preg_match('/^https?:\/\//', $this->cover_image))
                return env('APP_URL').$this->cover_image;

            return $this->cover_image;
        } elseif($this->has_photos()) {
            return env('APP_URL').$this->photos[0]->full_url;
        }

        return null;
    }

    public function field_is_from_ics_invite($field) {
        $fields = json_decode($this->fields_from_ics);
        if(!is_array($fields))
            return false;
        return in_array($field, $fields);
    }

    public function schedule_zoom_meeting() {
        if(Setting::value('zoom_client_id')) {
            $meeting = Zoom::schedule_meeting($this);
            if(!$meeting) {
                return false;
            }
            $this->meeting_url = $meeting['join_url'];
            $this->zoom_meeting_id = $meeting['id'];
        }
        return true;
    }

    public function update_zoom_meeting() {
        if(Setting::value('zoom_client_id')) {
            Zoom::update_meeting($this);
        }
    }

    public static function used_timezones() {
        return Event::select('timezone')
            ->whereNotNull('timezone')
            ->groupBy('timezone')
            ->orderBy(DB::raw('COUNT(id)'), 'DESC')
            ->get();
    }

    public static function timezones() {
        $used = self::used_timezones();

        $timezones = [''];

        // First add all the timezones that have been used
        foreach($used as $tz)
            $timezones[] = $tz->timezone;

        $timezones[] = '──────────';

        // Then add all the known timezones if they aren't already in the list
        foreach(DateTimeZone::listIdentifiers(DateTimeZone::ALL) as $tz) {
            if(!in_array($tz, $timezones))
                $timezones[] = $tz;
        }

        return $timezones;
    }

    // Since converting from -07:00 to America/Los_Angeles is a fuzzy concept,
    // let's skew the conversion based on the most frequently used timezones in the database.
    public static function timezone_name_from_offset(string $offset, DateTime $date) {
        $timezones = self::timezones();
        foreach($timezones as $tz) {
            try {
                $timezone = new DateTimeZone($tz);
                $seconds = $timezone->getOffset($date);
                if(\p3k\date\tz_seconds_to_offset($seconds) == $offset)
                    return $tz;
            } catch(\Exception $e) {}
        }
        return null;
    }

    public static function hms_to_sec($hms) {
        $parts = explode(':', $hms);
        return $parts[2] + ($parts[1]*60) + ($parts[0]*60*60);
    }

}
