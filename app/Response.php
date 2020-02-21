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
        return $this->hasMany('App\ResponsePhoto')
            ->where('approved', true);
    }

    public function author() {
        if($this->rsvp_user_id) {
            $user = User::where('id', $this->rsvp_user_id)->first();
            return [
                'name' => $user->name,
                'photo' => $user->photo,
                'url' => $user->url,
            ];
        } else {
            return [
                'name' => $this->author_name,
                'photo' => $this->author_photo,
                'url' => $this->author_url,
            ];
        }
    }

    public function author_photo() {
        if($this->rsvp_user_id) {
            $user = User::where('id', $this->rsvp_user_id)->first();
            return $user->photo;
        } else {
            return $this->author_photo;
        }
    }

    public function author_display_name() {
        $author = $this->author();

        if(!empty($author['name']))
            return $author['name'];

        if(!empty($author['url']))
            return \p3k\url\display_url($author['url']);

        return \p3k\url\display_url($this->link());
    }

    public function link() {
        return $this->url ?: $this->source_url;
    }

    public function rsvp_link() {
        $author = $this->author();
        if(!empty($author['url']))
            return $author['url'];
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
