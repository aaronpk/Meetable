<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use DB, Auth, Log, Schema;
use App\Event;

class Setting extends Model
{
    protected $keyType = 'string';

    static $cached = [];

    public static function value($id) {
        if(!isset($_ENV['DYNO']) && !file_exists(__DIR__.'/../.env')) {
            return null;
        }

        if(!Schema::hasTable('settings'))
            return null;

        if(isset(self::$cached[$id]))
            return self::$cached[$id];

        // Load all settings into the cache
        $settings = Setting::get();
        foreach($settings as $s) {
            self::$cached[$s->id] = $s->value;
        }

        return self::$cached[$id] ?? null;
    }

    public static function html_value($id) {
        $value = self::value($id);

        if(!$value)
            return '';

        $markdown = $value;

        $html = \Michelf\MarkdownExtra::defaultTransform($markdown);
        $html = \p3k\HTML::sanitize($html);

        return $html;
    }

    public static function set($id, $value) {
        $setting = self::where('id', $id)->first();
        if(!$setting) {
            $setting = new Setting;
            $setting->id = $id;
        } else {
            // Save a snapshot of the current revision
            $revision = new SettingRevision;
            $revision->setting_id = $id;
            foreach(['value', 'last_saved_by', 'created_at', 'updated_at'] as $key)
                $revision->{$key} = $setting->{$key};
            $revision->save();
        }
        if(Auth::user())
            $setting->last_saved_by = Auth::user()->id;
        $setting->value = $value;
        $setting->save();
    }

    public static function event_page_embed(Event $event) {
        return str_replace('%EVENT_URL%', $event->absolute_permalink(), self::value('event_page_embed'));
    }

}
