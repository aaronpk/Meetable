<?php

namespace App\Http\Middleware;

use App\Helpers\Locales;
use Closure;
use Illuminate\Http\Request;

/**
 * Shows each page in the visitor's language: the one they picked from the language
 * menu, or else the best match for their browser's Accept-Language header, or else
 * the site's language.
 */
class SetLocale
{
    const COOKIE = 'locale';

    public function handle(Request $request, Closure $next)
    {
        $available = Locales::available();

        $chosen = $request->cookie(self::COOKIE);

        if(is_string($chosen) && in_array($chosen, $available)) {
            $locale = $chosen;
        } else {
            // Returns the first locale, the site's language, when nothing matches
            $locale = $request->getPreferredLanguage($available);
        }

        app()->setLocale($locale);

        $response = $next($request);

        // The same URL can be a different language for different visitors
        $response->headers->set('Vary', 'Accept-Language, Cookie', false);

        return $response;
    }
}
