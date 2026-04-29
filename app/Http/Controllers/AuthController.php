<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const REMEMBER_COOKIE_NAME = 'koperasi_remember';
    private const REMEMBER_DURATION_MINUTES = 86400; // 60 hari

    public function showLogin()
    {
        if ($this->restoreRememberedSession(request())) {
            return redirect()->route('dashboard');
        }

        if (session()->has('auth')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
        ]);

        $identifier = trim($credentials['email']);
        $user = DB::table('users')
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('nip', $identifier);
            })
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak cocok.'])
                ->withInput([
                    'email' => $identifier,
                    'remember' => $request->boolean('remember'),
                ]);
        }

        if (isset($user->status) && $user->status !== 'active') {
            return back()
                ->withErrors(['email' => 'Akun Anda tidak aktif. Hubungi admin.'])
                ->withInput([
                    'email' => $identifier,
                    'remember' => $request->boolean('remember'),
                ]);
        }

        $remember = $request->boolean('remember');

        $request->session()->put('auth', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'two_factor_enabled' => (bool) ($user->two_factor_enabled ?? false),
            'two_factor_passed' => !(bool) ($user->two_factor_enabled ?? false),
        ]);

        $request->session()->regenerate();

        if ($remember) {
            $rememberToken = Str::random(64);

            DB::table('users')
                ->where('id', $user->id)
                ->update(['remember_token' => $rememberToken]);

            Cookie::queue(
                cookie(
                    self::REMEMBER_COOKIE_NAME,
                    $user->id . '|' . $rememberToken,
                    self::REMEMBER_DURATION_MINUTES,
                    '/',
                    null,
                    null,
                    true,
                    false,
                    'lax'
                )
            );
        } else {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['remember_token' => null]);
            Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE_NAME));
        }

        if (!empty($user->two_factor_enabled)) {
            return redirect()->route('authenticator.verify');
        }

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $auth = $request->session()->get('auth');
        if (!empty($auth['id'])) {
            DB::table('users')
                ->where('id', $auth['id'])
                ->update(['remember_token' => null]);
        }

        $request->session()->forget('auth');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE_NAME));

        return redirect()->route('login');
    }

    private function restoreRememberedSession(Request $request): bool
    {
        if ($request->session()->has('auth')) {
            return true;
        }

        $rememberCookie = (string) $request->cookie(self::REMEMBER_COOKIE_NAME, '');
        if ($rememberCookie === '' || strpos($rememberCookie, '|') === false) {
            return false;
        }

        [$userId, $rememberToken] = explode('|', $rememberCookie, 2);
        if ($userId === '' || $rememberToken === '') {
            Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE_NAME));
            return false;
        }

        $user = DB::table('users')
            ->where('id', (int) $userId)
            ->where('status', 'active')
            ->first();

        if (!$user || empty($user->remember_token) || !hash_equals((string) $user->remember_token, $rememberToken)) {
            Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE_NAME));
            return false;
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

        return true;
    }
}
