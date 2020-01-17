<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
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
        if(!$request->expectsJson()) {
            // Get the URL to redirect to from the active Guard.
            // This allows different guards to implement this differently.
            return Auth::guard()->redirectWhenNotAuthenticated($request->url());
        } else {
            return abort(401);
        }
    }
}
