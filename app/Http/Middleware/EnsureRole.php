<?php

namespace App\Http\Middleware;

use Closure;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $roles
     * @return mixed
     */
    public function handle($request, Closure $next, $roles)
    {
        $authUser = $request->session()->get('auth');

        if (!$authUser) {
            return redirect()->route('login');
        }

        if (($authUser['role'] ?? '') === 'superadmin') {
            return $next($request);
        }

        $allowed = collect(explode('|', $roles))
            ->map(function ($role) {
                return trim($role);
            })
            ->filter()
            ->contains($authUser['role'] ?? '');

        if (!$allowed) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
