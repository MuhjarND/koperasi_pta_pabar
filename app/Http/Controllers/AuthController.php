<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('auth')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = DB::table('users')
            ->where('email', $credentials['email'])
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak cocok.'])
                ->withInput(['email' => $credentials['email']]);
        }

        if (isset($user->status) && $user->status !== 'active') {
            return back()
                ->withErrors(['email' => 'Akun Anda tidak aktif. Hubungi admin.'])
                ->withInput(['email' => $credentials['email']]);
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

        if (!empty($user->two_factor_enabled)) {
            return redirect()->route('authenticator.verify');
        }

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('auth');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
