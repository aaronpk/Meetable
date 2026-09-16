<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $hidden = [
        'id', 'pivot', 'created_at', 'updated_at'
    ];

    public function events() {
        return $this->belongsToMany('\App\Event');
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

    // Finds an existing tag without creating one, for pages that only display tags.
    // If the tag doesn't exist, returns an unsaved Tag so it can still be shown and queried (matching no events).
    public static function lookup($tag_name) {
        $tag_name = self::normalize($tag_name);

        $tag = Tag::where('tag', $tag_name)->first();
        if(!$tag) {
            $tag = new Tag();
            $tag->tag = $tag_name;
        }
        return $tag;
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
