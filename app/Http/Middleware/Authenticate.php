<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
//        if (Auth::guard($guard)->guest()) {
//            if ($request->ajax() || $request->wantsJson()) {
//                return response('Unauthorized.', 401);
//            } else {
////                return redirect()->guest('login');
//                return redirect()->guest('/api');
//            }
//        }

        // 如果未登录，返回401
        if (Auth::guard($guard)->guest()) {
            return response($this->unauthJson(), 401);
        }

        return $next($request);
    }

    /*
     * 返回未认证的 Json 字符串.
     *
     * @return string $messages
     */
    private function unauthJson()
    {
        return $messages = '
            {"messages": "Unauthorized."}
        ';
    }
}
