<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Response extends Model
{
    use SoftDeletes;

    protected $hidden = [
        'id', 'event_id', 'rsvp_user_id', 'created_by', 'approved_by',
    ];

    public function event() {
        return $this->belongsTo('\App\Event');
    }

    public function creator() {
        return $this->belongsTo('\App\User', 'created_by', 'id');
    }

    public function approvedBy() {
        return $this->belongsTo('\App\User', 'approved_by', 'id');
    }

    public function photos() {
        return $this->hasMany('App\ResponsePhoto');
    }

    public function html_cleaned() {
        // XRay already sanitizes the HTML, but it allows microformats classes. we remove them here.
        return \p3k\HTML::sanitize($this->content_html, ['allowMf2' => false]);
    }

    public function author_photo() {
        if($this->rsvp_user_id) {
            $user = User::where('id', $this->rsvp_user_id)->first();
            return $user->photo ?: $this->author_photo;
        } else {
            return $this->author_photo;
        }
    }

    public function author_display_name() {
        if($this->rsvp_user_id) {
            $user = User::where('id', $this->rsvp_user_id)->first();
        } else {
            $user = null;
        }

        if($user && $user->name) 
            return $user->name;

        if($this->author_name)
            return $this->author_name;

        // Fall back to domain name if nothing else is available
        return parse_url($this->author_url($user), PHP_URL_HOST);
    }

    public function author_url($user=null) {
        if(!$user) {
            if($this->rsvp_user_id) {
                $user = User::where('id', $this->rsvp_user_id)->first();
            } else {
                $user = null;
            }
        }

        if($user && $user->url)
            $url = $user->url;
        elseif($this->author_url)
            $url = $this->author_url;
        else
            $url = $this->link();

        return $url;
    }

    public function link() {
        return $this->url ?: $this->source_url;
    }

    public function rsvp_link() {
        if($this->rsvp_user_id) {
            $user = User::where('id', $this->rsvp_user_id)->first();
            return $user->url;
        }
        return $this->link();
    }

    public function set_photo_alt($url, $alt) {
        $alts = $this->photo_alt;
        if(!is_array($alts))
            $alts = [];
        $alts[$url] = $alt;
        $this->photo_alt = $alts;
    }

    public function photo_original_url() {
        return $this->link() ?: ($this->creator ? $this->creator->url : '');
    }

    public function photo_author_name() {
        if($this->author_name)
            return $this->author_name;

        if($this->creator) {
            return $this->creator->name ?: parse_url($this->creator->url, PHP_URL_HOST);
        }

        if($this->link())
            return parse_url($this->link(), PHP_URL_HOST);

        return '';
    }

    // https://laravel.com/docs/5.7/eloquent-mutators
    // Ensure null values instead of empty strings

    public function setAuthorNameAttribute($value) {
        $this->attributes['author_name'] = $value ?: null;
    }

    public function setAuthorPhotoAttribute($value) {
        $this->attributes['author_photo'] = $value ?: null;
    }

    public function setAuthorUrlAttribute($value) {
        $this->attributes['author_url'] = $value ?: null;
    }

    public function setNameAttribute($value) {
        $this->attributes['name'] = $value ?: null;
    }

    public function setContentTextAttribute($value) {
        $this->attributes['content_text'] = $value ?: null;
    }

    public function setContentHTMLAttribute($value) {
        $this->attributes['content_html'] = $value ?: null;
    }

    public function setRsvpAttribute($value) {
        $value = strtolower($value);
        $this->attributes['rsvp'] = in_array($value, ['yes','no','maybe','remote']) ? $value : null;
    }

    public function getDataAttribute() {
        if(!$this->attributes['data'])
            return '';

        return json_encode(json_decode($this->attributes['data']), JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
    }

}
