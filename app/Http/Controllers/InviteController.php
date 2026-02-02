<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InviteController extends Controller
{
    public function index()
    {
        $invites = DB::table('member_invites')
            ->leftJoin('users as creator', 'member_invites.created_by', '=', 'creator.id')
            ->leftJoin('users as used', 'member_invites.used_by', '=', 'used.id')
            ->select(
                'member_invites.*',
                'creator.name as creator_name',
                'used.name as used_name'
            )
            ->orderByDesc('member_invites.created_at')
            ->limit(50)
            ->get();

        return view('invites.index', [
            'invites' => $invites,
        ]);
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'member_no' => 'nullable|string|max:30|unique:users,member_no|unique:member_invites,member_no',
            'email' => 'nullable|email|max:150|unique:users,email',
            'note' => 'nullable|string|max:500',
            'expires_in' => 'nullable|integer|min:1|max:30',
        ]);

        $expiresAt = null;
        if (!empty($payload['expires_in'])) {
            $expiresAt = now()->addDays((int) $payload['expires_in']);
        }

        do {
            $token = Str::random(48);
            $exists = DB::table('member_invites')->where('token', $token)->exists();
        } while ($exists);

        DB::table('member_invites')->insert([
            'token' => $token,
            'member_no' => $payload['member_no'] ?? null,
            'email' => $payload['email'] ?? null,
            'note' => $payload['note'] ?? null,
            'expires_at' => $expiresAt,
            'created_by' => $request->session()->get('auth.id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('invites.index')
            ->with('success', 'Link pendaftaran berhasil dibuat.');
    }
}
