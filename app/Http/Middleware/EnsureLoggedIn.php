<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\View;

class EnsureLoggedIn
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
        if (!$request->session()->has('auth')) {
            return redirect()->route('login');
        }

        View::share('authUser', $request->session()->get('auth'));

        return $next($request);
    }
}
