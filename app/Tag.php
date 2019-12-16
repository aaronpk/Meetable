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

    public static function normalize($tag_name) {
        $tag_name = mb_strtolower(trim($tag_name, ', '));
        # https://unicode-table.com/en/#00E0
        $tag_name = mb_ereg_replace('[^a-z0-9à-öø-ÿāăąćĉċčŏœ]+', '-', $tag_name);
        $tag_name = trim($tag_name, '-'); // remove trailing hyphens
        return $tag_name;
    }

    public static function get($tag_name) {
        $tag_name = self::normalize($tag_name);

        $tag = Tag::where('tag', $tag_name)->first();
        if(!$tag) {
            $tag = new Tag();
            $tag->tag = $tag_name;
            $tag->save();
        }
        return $tag;
    }
}
