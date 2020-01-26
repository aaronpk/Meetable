<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use DB, Auth;

class Setting extends Model
{

    public static function value($id) {
        $setting = self::where('id', $id)->first();
        return $setting ? $setting->value : null;
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

}
