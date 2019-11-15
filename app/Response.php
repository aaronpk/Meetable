<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Response extends Model
{
    public function event() {
        return $this->belongsTo('\App\Event');
    }

    public function photos() {
        if(!$this->photos)
            return [];

        return json_decode($this->photos, true);
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
}
