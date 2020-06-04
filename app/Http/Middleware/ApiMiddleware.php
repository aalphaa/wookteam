<?php

namespace App\Http\Middleware;

use Closure;
use Request;

class ApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        global $_A;
        $_A = [];

        @error_reporting(E_ALL & ~E_NOTICE);

        if (Request::input('__Access-Control-Allow-Origin') || Request::header('__Access-Control-Allow-Origin')) {
            header('Access-Control-Allow-Origin:*');
            header('Access-Control-Allow-Methods:GET,POST,PUT,DELETE,OPTIONS');
            header('Access-Control-Allow-Headers:Content-Type, platform, platform-channel, token, release, Access-Control-Allow-Origin');
        }

        return $next($request);
    }
}
