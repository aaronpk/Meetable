<?php

namespace App\Http\Middleware;

use App\Services\Auth\CustomGuard;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Route;
use Auth;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        $guard = Auth::guard();

        // The external auth methods send the visitor off to their own login flow.
        if($guard instanceof CustomGuard) {
            return $guard->redirectWhenNotAuthenticated($request->url());
        }

        // The built-in passkey login is served by this app. It isn't registered
        // until the site has been set up, so fall back to the installer.
        return Route::has('login') ? route('login') : '/';
    }
}
