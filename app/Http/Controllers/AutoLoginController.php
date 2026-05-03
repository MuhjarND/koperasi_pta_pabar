<?php

namespace App\Http\Controllers;

use App\Services\ChatbotGatewayService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AutoLoginController extends Controller
{
    public function show(Request $request)
    {
        $token = $this->tokenFromRequest($request);

        if ($token === '') {
            return $this->errorResponse();
        }

        if ($request->session()->has('auth')) {
            return redirect()->route('dashboard');
        }

        return response()
            ->view('auth.autologin-continue', compact('token'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function login(Request $request, ChatbotGatewayService $gateway)
    {
        $token = (string) $request->input('token', '');

        if ($token === '') {
            return $this->errorResponse();
        }

        $validation = $gateway->validateMagicToken($token);

        if (empty($validation['valid']) || empty($validation['app_user_id'])) {
            if ($request->session()->has('auth')) {
                return redirect()->route('dashboard');
            }

            return $this->errorResponse();
        }

        $appUserId = (string) $validation['app_user_id'];
        Log::info('Chatbot magic login user lookup started', [
            'app_user_id_hash' => substr(hash('sha256', $appUserId), 0, 16),
            'app_user_id_length' => strlen($appUserId),
            'app_user_id_is_numeric' => is_numeric($appUserId),
        ]);

        $user = $this->findUser($appUserId);

        if (!$user) {
            Log::warning('Chatbot magic login user not found', [
                'app_user_id_hash' => substr(hash('sha256', $appUserId), 0, 16),
            ]);

            return $this->errorResponse();
        }

        if (isset($user->status) && $user->status !== 'active') {
            Log::warning('Chatbot magic login inactive user', [
                'user_id' => $user->id,
            ]);

            return $this->errorResponse();
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

        Log::info('Chatbot magic login user matched', [
            'user_id' => $user->id,
            'role' => $user->role,
        ]);

        return redirect()->route('dashboard');
    }

    private function findUser(string $appUserId)
    {
        $query = User::query()->where('id', $appUserId);

        if (Schema::hasColumn('users', 'app_user_id')) {
            $query->orWhere('app_user_id', $appUserId);
        }

        if (Schema::hasColumn('users', 'nip')) {
            $query->orWhere('nip', $appUserId);
        }

        if (filter_var($appUserId, FILTER_VALIDATE_EMAIL)) {
            $query->orWhere('email', $appUserId);
        }

        $user = $query->first();

        if ($user) {
            return $user;
        }

        return DB::table('users')
            ->where('id', $appUserId)
            ->first();
    }

    private function errorResponse()
    {
        return response()
            ->view('auth.autologin-error', [
                'message' => config('chatbot.autologin_error_message'),
            ], 401);
    }

    private function tokenFromRequest(Request $request)
    {
        $rawQuery = (string) $request->server('QUERY_STRING', '');

        foreach (explode('&', $rawQuery) as $part) {
            if ($part === '') {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');

            if (rawurldecode($key) === 'token') {
                return rawurldecode($value);
            }
        }

        return (string) $request->query('token', '');
    }
}
