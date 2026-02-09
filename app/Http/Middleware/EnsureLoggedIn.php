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

        $authUser = $request->session()->get('auth');
        View::share('authUser', $authUser);

        $requires2fa = !empty($authUser['two_factor_enabled']);
        $passed2fa = !empty($authUser['two_factor_passed']);
        if ($requires2fa && !$passed2fa) {
            if (!$request->routeIs('authenticator.verify', 'authenticator.verify.submit', 'logout')) {
                if (!$request->session()->has('two_factor_intended')) {
                    $request->session()->put('two_factor_intended', $request->fullUrl());
                }
                return redirect()->route('authenticator.verify');
            }
        }

        return $next($request);
    }
}
