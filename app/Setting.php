<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use DB, Auth, Log, Schema;
use App\Event;

class Setting extends Model
{
    protected $keyType = 'string';

    static $cached = [];

    // Whether all settings have been loaded into the cache for this request
    static $loaded = false;

    // Whether the settings table is known to exist
    static $table_exists = false;

    public static function value($id) {
        if(!isset($_ENV['DYNO']) && !file_exists(__DIR__.'/../.env')) {
            return null;
        }

        // Checking the schema is a query against information_schema, so remember a table that
        // was found. One that wasn't is checked again, as the installer creates it part way through.
        if(!self::$table_exists)
            self::$table_exists = Schema::hasTable('settings');

        if(!self::$table_exists)
            return null;

        // Load all settings into the cache the first time any setting is read. A setting that
        // has no value must not trigger another load, so track that the load happened rather
        // than checking for the value.
        if(!self::$loaded) {
            foreach(Setting::get() as $s) {
                self::$cached[$s->id] = $s->value;
            }
            self::$loaded = true;
        }

        return self::$cached[$id] ?? null;
    }

    // Forget the cached settings so the next read loads them from the database again
    public static function clear_cache() {
        self::$cached = [];
        self::$loaded = false;
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
        if(!app()->runningInConsole() && Auth::user())
            $setting->last_saved_by = Auth::user()->id;
        $setting->value = $value;
        $setting->save();

        self::$cached[$id] = $value;
    }

    public static function event_page_embed(Event $event) {
        return str_replace('%EVENT_URL%', $event->absolute_permalink(), self::value('event_page_embed'));
    }

}
