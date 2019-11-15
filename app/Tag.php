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

    public static function get($tag_name) {
        $tag_name = strtolower(trim($tag_name));
        $tag_name = preg_replace('/[^a-z0-9]/', '-', $tag_name);

        $tag = Tag::where('tag', $tag_name)->first();
        if(!$tag) {
            $tag = new Tag();
            $tag->tag = $tag_name;
            $tag->save();
        }
        return $tag;
    }
}
