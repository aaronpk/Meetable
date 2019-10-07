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
}
