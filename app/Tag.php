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
        return '/tag/' . rawurlencode($this->tag);
    }

    // Keeps letters and numbers from any script, like event slugs, so "東京" and "Düsseldorf"
    // are tags too. Normalizer comes from symfony/polyfill-intl-normalizer without intl.
    public static function normalize($tag_name) {
        $tag_name = \Normalizer::normalize((string)$tag_name, \Normalizer::FORM_C) ?: (string)$tag_name;
        $tag_name = mb_strtolower(trim($tag_name, ', '));
        $tag_name = preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', $tag_name) ?? '';
        $tag_name = trim($tag_name, '-'); // remove leading and trailing hyphens
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
