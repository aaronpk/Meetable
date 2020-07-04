<?php
namespace App\Http\Middleware;

use Illuminate\Support\Str;
use Closure;

class SlashesMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     * Use in route definitions:
     * Route::get('/compare', 'Controller@compare')->middleware("slashes:add");
     * Route::get('/compare', 'Controller@compare')->middleware("slashes:remove");
     */
    public function handle($request, Closure $next, $flag)
    {
        if($flag=="remove") {
            if(Str::endsWith($request->getPathInfo(), '/')) {
                $path = rtrim($request->getPathInfo(), "/");
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: ".$path);
                exit();
            }
        } else {
            if(!Str::endsWith($request->getPathInfo(), '/')) {
                $path = $request->getPathInfo().'/';
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: ".$path);
                exit();
            }
        }
        return $next($request);
    }
}
