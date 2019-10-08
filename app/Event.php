<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use DateTime, DateTimeZone;

class Event extends Model
{

    public function responses() {
        return $this->hasMany('\App\Response');
    }

    public function tags() {
        return $this->belongsToMany('\App\Tag');
    }

    public function tags_string() {
        $tags = [];
        foreach($this->tags as $t)
            $tags[] = $t->tag;
        return implode(' ', $tags);
    }

    public function has_rsvps() {
        return $this->responses->where('type', 'rsvp')->count();
    }

    public function has_photos() {
        return $this->responses->where('type', 'photo')->count();
    }

    public function has_posts() {
        return $this->responses->where('type', 'post')->count();
    }

    public function has_comments() {
        return $this->responses->where('type', 'comment')->count();
    }

    public function rsvps() {
        return $this->hasMany('\App\Response')->where('type', 'rsvp');
    }

    public function photos() {
        return $this->hasMany('\App\Response')->where('type', 'photo');
    }

    public function posts() {
        return $this->hasMany('\App\Response')->where('type', 'post');
    }

    public function comments() {
        return $this->hasMany('\App\Response')->where('type', 'comment');
    }

    public function permalink() {
        $date = new DateTime($this->start_date);
        return '/' . $date->format('Y') . '/' . $date->format('m') . '/' . ($this->slug ? $this->slug.'-' : '') . $this->key;
    }

    public function date_summary() {
        $start_date = new DateTime($this->start_date);

        if($this->end_date) {
            $end_date = new DateTime($this->end_date);

            return $start_date->format('F j') . ' - ' . $end_date->format('F j, Y');

        } else {
            return $start_date->format('F j, Y');
        }
    }

    public function location_summary() {
        $str = [];
        if($this->location_address) $str[] = $this->location_address;
        if($this->location_locality) $str[] = $this->location_locality;
        if($this->location_region) $str[] = $this->location_region;
        if($this->location_country) $str[] = $this->location_country;
        return implode(', ', $str);
    }

    public function html() {
        if(!$this->description)
            return '';

        $markdown = $this->description;

        $html = \Michelf\MarkdownExtra::defaultTransform($markdown);

        $html = Utils\HTML::sanitizeHTML($html);

        return $html;
    }

}
