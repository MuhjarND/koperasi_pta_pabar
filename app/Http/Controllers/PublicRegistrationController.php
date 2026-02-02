<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PublicRegistrationController extends Controller
{
    public function show($token)
    {
        $invite = $this->getInvite($token);

        if (!$invite) {
            return view('public.register', [
                'invite' => null,
                'error' => 'Link pendaftaran tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        return view('public.register', [
            'invite' => $invite,
            'error' => null,
        ]);
    }

    public function store(Request $request, $token)
    {
        $invite = $this->getInvite($token);

        if (!$invite) {
            return redirect()
                ->route('public.register', $token)
                ->withErrors(['link' => 'Link pendaftaran tidak valid atau sudah kedaluwarsa.']);
        }

        $payload = $request->validate([
            'name' => 'nullable|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'address' => 'nullable|string|max:255',
            'password' => 'required|string|min:6|max:60|confirmed',
        ]);

        if ($invite->email && $invite->email !== $payload['email']) {
            return back()->withErrors(['email' => 'Email tidak sesuai dengan undangan.'])->withInput();
        }

        $memberNo = $invite->member_no ?: $this->generateMemberNo();
        if (DB::table('users')->where('member_no', $memberNo)->exists()) {
            $memberNo = $this->generateMemberNo();
        }

        $name = trim($payload['name'] ?? '');
        if ($name === '') {
            $name = 'Anggota ' . $memberNo;
        }

        DB::beginTransaction();

        try {
            $userId = DB::table('users')->insertGetId([
                'name' => $name,
                'email' => $payload['email'],
                'password' => Hash::make($payload['password']),
                'role' => 'anggota',
                'member_no' => $memberNo,
                'address' => $payload['address'] ?? null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('member_invites')
                ->where('id', $invite->id)
                ->update([
                    'used_at' => now(),
                    'used_by' => $userId,
                    'updated_at' => now(),
                ]);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();

            return back()
                ->withErrors(['form' => 'Terjadi kesalahan. Silakan coba lagi.'])
                ->withInput();
        }

        return view('public.register-success');
    }

    private function getInvite($token)
    {
        $invite = DB::table('member_invites')
            ->where('token', $token)
            ->first();

        if (!$invite) {
            return null;
        }

        if ($invite->used_at) {
            return null;
        }

        if ($invite->expires_at && now()->greaterThan($invite->expires_at)) {
            return null;
        }

        return $invite;
    }

    private function generateMemberNo()
    {
        $prefix = 'A-';
        $numbers = DB::table('users')
            ->where('member_no', 'like', $prefix . '%')
            ->pluck('member_no');

        $max = 0;
        foreach ($numbers as $value) {
            if (preg_match('/(\d+)/', $value, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $prefix . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
    }
}
