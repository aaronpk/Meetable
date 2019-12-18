<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Response extends Model
{
    use SoftDeletes;

    # https://laravel.com/docs/5.7/eloquent-mutators#array-and-json-casting
    protected $casts = [
        'photos' => 'array',
    ];

    protected $hidden = [
        'id', 'event_id', 'rsvp_user_id', 'created_by',
    ];

    public static function image_proxy($url, $opts) {
        // https://github.com/willnorris/imageproxy
        $urlToSign = $url.'#'.$opts;
        $sig = strtr(base64_encode(hash_hmac('sha256', $urlToSign, env('IMAGE_PROXY_KEY'), 1)), '/+' , '_-');
        return env('IMAGE_PROXY_BASE').$opts.',s'.$sig.'/'.$url;
    }

    public function event() {
        return $this->belongsTo('\App\Event');
    }

    public function creator() {
        return $this->belongsTo('\App\User', 'created_by', 'id');
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

    public function link() {
        return $this->url ?: $this->source_url;
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

}
