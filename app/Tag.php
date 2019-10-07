<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    public function events() {
        $this->belongsToMany('\App\Event');
    }

    public function url() {
        return '/tag/' . $this->tag;
    }
}
