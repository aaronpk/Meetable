<?php
namespace App\Helpers;

class Locales {

    // The site's own language, from APP_LOCALE
    public static function site() {
        return config('app.site_locale');
    }

    // The languages visitors can be shown, with the site's language first
    public static function available() {
        $locales = config('app.available_locales');

        if($locales === null) {
            $locales = array_map('basename', glob(resource_path('lang/*'), GLOB_ONLYDIR));
        }

        return array_values(array_unique(array_merge([self::site()], $locales)));
    }

    // A language's name in that language, like "Deutsch", or its code if its translation doesn't name it
    public static function name($locale) {
        if(!\Lang::has('common.language_name', $locale, false))
            return $locale;

        return __('common.language_name', [], $locale);
    }

    // Runs $callback with the site's language, for text that isn't only shown to the
    // current visitor, like messages posted to Discord
    public static function inSiteLocale(callable $callback) {
        $current = app()->getLocale();

        if($current == self::site())
            return $callback();

        app()->setLocale(self::site());
        try {
            return $callback();
        } finally {
            app()->setLocale($current);
        }
    }

}
