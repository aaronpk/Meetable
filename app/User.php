<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Storage, Log;
use Image;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'url'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'api_token', 'created_at', 'updated_at', 'is_admin',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];

    public function display_name() {
        return $this->name ?: \p3k\url\display_url($this->url);
    }

    public function downloadProfilePhoto($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, \App\Helpers\HTTP::user_agent());
        $original_image = curl_exec($ch);
        $err = curl_errno($ch);
        curl_close($ch);

        if($original_image && $err == 0) {
            // Resize to 150px square
            try {
                $image = Image::make($original_image);
                $image->fit(150, 150);

                $filename = 'public/users/'.$this->id.'-'.md5($url).'.jpg';
                Storage::put($filename, $image->stream('jpg', 80));
                Storage::setVisibility($filename, 'public');

                return Storage::url($filename);
            } catch(\Exception $e) {
                Log::error('Reading image at '.$url.' failed: '.$e->getMessage());
            }
        } else {
            Log::error('Downloading profile photo at '.$url.' failed: '.curl_error($ch));
        }
    }
}
