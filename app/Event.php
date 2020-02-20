<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DateTime, DateTimeZone;
use DB;

class Event extends Model
{
    use SoftDeletes;

    protected $hidden = [
        'id',
    ];

    protected $appends = [
        'tag_list',
    ];

    private static $US_NAMES = ['US', 'USA', 'United States'];

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

    public function photos() {
        return $this->hasMany('\App\ResponsePhoto')
            ->where('approved', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc');
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

    public function tags_string() {
        $tags = [];
        foreach($this->tags as $t)
            $tags[] = $t->tag;
        return implode(' ', $tags);
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

    public function permalink() {
        $date = new DateTime($this->start_date);
        return '/' . $date->format('Y') . '/' . $date->format('m') . '/' . ($this->slug ? $this->slug.'-' : '') . $this->key;
    }

    public function ics_permalink() {
        return '/ics' . $this->permalink() . '.ics';
    }

    public function absolute_permalink() {
        return env('APP_URL').$this->permalink();
    }

    public function is_multiday() {
        return $this->end_date && $this->end_date != $this->start_date;
    }

    public function date_summary() {
        $start_date = new DateTime($this->start_date);

        if($this->is_multiday()) {
            $end_date = new DateTime($this->end_date);

            return '<time datetime="'.$start_date->format('Y-m-d').'">'
                    . $start_date->format('M j')
                    . '</time> - '
                    . '<time datetime="'.$end_date->format('Y-m-d').'">'
                    . ($end_date->format('m') == $start_date->format('m') ? $end_date->format('j, Y') : $end_date->format('M j, Y'))
                    . '</time>';

        } else {
            if($this->start_time) {
                $start = new DateTime($this->start_date.' '.$this->start_time);
                return '<time datetime="'.$start->format('Y-m-d H:i').'">'
                        . $start->format('M j, Y g:ia')
                        . '</time>';
            } else {
                return '<time datetime="'.$start_date->format('Y-m-d').'">'
                        . $start_date->format('M j, Y')
                        . '</time>';
            }
        }
    }

    public function start_datetime_local() {
        $start_date = new DateTime($this->start_date.' '.$this->start_time);
        return $start_date->format('Ymd\THi');
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

    public function weekday() {
        $start_date = new DateTime($this->start_date);
        return $start_date->format('D');
    }

    public function start_and_end_dates() {
        $start = false;
        $end = false;

        if($this->start_date && !$this->start_time && !$this->end_date && !$this->end_time) {
            $start = (new DateTime($this->start_date))->format('Y-m-d');
        }
        elseif($this->start_date && !$this->start_time && $this->end_date && !$this->end_time) {
            $start = (new DateTime($this->start_date))->format('Y-m-d');
            $end = (new DateTime($this->end_date))->format('Y-m-d');
        }
        elseif($this->start_date && $this->start_time && !$this->end_date && !$this->end_time) {
            if($this->timezone)
                $start = (new DateTime($this->start_date.' '.$this->start_time, new DateTimeZone($this->timezone)))->format('Y-m-d\TH:i:sP');
            else
                $start = (new DateTime($this->start_date.' '.$this->start_time))->format('Y-m-d\TH:i:s');
        }
        elseif($this->start_date && $this->start_time && !$this->end_date && $this->end_time) {
            if($this->timezone) {
                $start = (new DateTime($this->start_date.' '.$this->start_time, new DateTimeZone($this->timezone)))->format('Y-m-d\TH:i:sP');
                $end = (new DateTime($this->start_date.' '.$this->end_time, new DateTimeZone($this->timezone)))->format('Y-m-d\TH:i:sP');
            } else {
                $start = (new DateTime($this->start_date.' '.$this->start_time))->format('Y-m-d\TH:i:s');
                $end = (new DateTime($this->start_date.' '.$this->end_time))->format('Y-m-d\TH:i:s');
            }
        }
        elseif($this->start_date && $this->start_time && $this->end_date && $this->end_time) {
            if($this->timezone) {
                $start = (new DateTime($this->start_date.' '.$this->start_time, new DateTimeZone($this->timezone)))->format('Y-m-d\TH:i:sP');
                $end = (new DateTime($this->end_date.' '.$this->end_time, new DateTimeZone($this->timezone)))->format('Y-m-d\TH:i:sP');
            } else {
                $start = (new DateTime($this->start_date.' '.$this->start_time))->format('Y-m-d\TH:i:s');
                $end = (new DateTime($this->end_date.' '.$this->end_time))->format('Y-m-d\TH:i:s');
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

    public function is_past() {
        if($this->timezone) {
            $tz = new DateTimeZone($this->timezone);
        } else {
            // Show "I'm going" unless the event is for sure past.
            // Fall back to last timezone.
            $tz = new DateTimeZone('-12:00');
        }

        if($this->end_date) {
            $date = new DateTime($this->end_date.' '.$this->end_time, $tz);
        } else {
            $date = new DateTime($this->start_date.' '.($this->end_time ?: $this->start_time), $tz);
        }

        $now = new DateTime();

        return $date->format('U') < $now->format('U');
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

    public function html() {
        if(!$this->description)
            return '';

        $markdown = $this->description;

        $html = \Michelf\MarkdownExtra::defaultTransform($markdown);

        $html = \p3k\HTML::sanitize($html);

        return $html;
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

        if($this->description)
            $data['description'] = substr($this->description, 0, 512).'...'; // google only shows a snippet

        list($start, $end) = $this->start_and_end_dates();

        $data['startDate'] = $start;
        if($end)
            $data['endDate'] = $end;

        if($this->cover_image) {
            $data['image'] = $this->cover_image_absolute_url();
        } elseif($this->has_photos()) {
            $data['image'] = $this->photos[0]->full_url;
        }

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
        // Return the absolute URL for the cover image, which is required by Twitter/FB
        // If the URL does not already start with http, assume it's stored locally and add the app URL
        if(!preg_match('/^https?:\/\//', $this->cover_image))
            return env('APP_URL').$this->cover_image;

        return $this->cover_image;
    }

    public static function used_timezones() {
        return Event::select('timezone')
            ->whereNotNull('timezone')
            ->groupBy('timezone')
            ->orderBy('timezone')
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
}
