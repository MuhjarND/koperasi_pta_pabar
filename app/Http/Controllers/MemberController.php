<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('users')
            ->where('role', 'anggota');

        if ($request->filled('q')) {
            $search = $request->get('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('member_no', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $members = $query
            ->orderBy('created_at', 'desc')
            ->get();

        return view('members.index', [
            'members' => $members,
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function create()
    {
        return view('members.create');
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'nip' => 'nullable|string|max:30',
            'unit_kerja' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|string|min:6|max:60',
        ]);

        $memberNo = $this->generateMemberNo();
        $password = $payload['password'] ?? 'koperasi123';

        DB::table('users')->insert([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => Hash::make($password),
            'role' => 'anggota',
            'member_no' => $memberNo,
            'nip' => $payload['nip'] ?? null,
            'unit_kerja' => $payload['unit_kerja'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'status' => $payload['status'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('members.index')
            ->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $member = DB::table('users')
            ->where('id', $id)
            ->where('role', 'anggota')
            ->first();

        if (!$member) {
            return redirect()->route('members.index');
        }

        return view('members.edit', [
            'member' => $member,
        ]);
    }

    public function update(Request $request, $id)
    {
        $payload = $request->validate([
            'member_no' => 'required|string|max:30|unique:users,member_no,' . $id,
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email,' . $id,
            'nip' => 'nullable|string|max:30',
            'unit_kerja' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|string|min:6|max:60',
        ]);

        $updates = [
            'name' => $payload['name'],
            'email' => $payload['email'],
            'member_no' => $payload['member_no'],
            'nip' => $payload['nip'] ?? null,
            'unit_kerja' => $payload['unit_kerja'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'status' => $payload['status'],
            'updated_at' => now(),
        ];

        if (!empty($payload['password'])) {
            $updates['password'] = Hash::make($payload['password']);
        }

        DB::table('users')
            ->where('id', $id)
            ->where('role', 'anggota')
            ->update($updates);

        return redirect()
            ->route('members.index')
            ->with('success', 'Data anggota berhasil diperbarui.');
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
