<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class EnsureLoggedIn
{
    private const REMEMBER_COOKIE_NAME = 'koperasi_remember';

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
            $this->restoreRememberedSession($request);
        }

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

    private function restoreRememberedSession($request): void
    {
        $rememberCookie = (string) $request->cookie(self::REMEMBER_COOKIE_NAME, '');
        if ($rememberCookie === '' || strpos($rememberCookie, '|') === false) {
            return;
        }

        [$userId, $rememberToken] = explode('|', $rememberCookie, 2);
        if ($userId === '' || $rememberToken === '') {
            Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE_NAME));
            return;
        }

        $user = DB::table('users')
            ->where('id', (int) $userId)
            ->where('status', 'active')
            ->first();

        if (!$user || empty($user->remember_token) || !hash_equals((string) $user->remember_token, $rememberToken)) {
            Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE_NAME));
            return;
        }

        $request->session()->put('auth', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'two_factor_enabled' => (bool) ($user->two_factor_enabled ?? false),
            'two_factor_passed' => !(bool) ($user->two_factor_enabled ?? false),
        ]);

        $request->session()->regenerate();
    }
}
