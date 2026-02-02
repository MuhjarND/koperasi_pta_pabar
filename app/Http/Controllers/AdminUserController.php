<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('users');

        if ($request->filled('q')) {
            $search = $request->get('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('member_no', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->get('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $users = $query
            ->orderBy('created_at', 'desc')
            ->get();

        return view('users.index', [
            'users' => $users,
            'filters' => $request->only(['q', 'role', 'status']),
            'roles' => config('koperasi.roles'),
        ]);
    }

    public function create()
    {
        return view('users.create', [
            'roles' => config('koperasi.roles'),
        ]);
    }

    public function store(Request $request)
    {
        $roles = implode(',', array_keys(config('koperasi.roles')));

        $payload = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'role' => 'required|in:' . $roles,
            'member_no' => 'nullable|string|max:30|unique:users,member_no',
            'nip' => 'nullable|string|max:30',
            'unit_kerja' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|string|min:6|max:60',
        ]);

        if ($payload['role'] === 'anggota') {
            $errors = [];
            if (empty($payload['nip'])) {
                $errors['nip'] = 'NIP wajib diisi untuk anggota.';
            }
            if (empty($payload['unit_kerja'])) {
                $errors['unit_kerja'] = 'Unit kerja wajib diisi untuk anggota.';
            }
            if (empty($payload['phone'])) {
                $errors['phone'] = 'No. HP wajib diisi untuk anggota.';
            }
            if ($errors) {
                return back()->withErrors($errors)->withInput();
            }
        }

        $password = $payload['password'] ?? 'koperasi123';
        $memberNo = $payload['member_no'] ?? null;
        if ($payload['role'] === 'anggota' && empty($memberNo)) {
            $memberNo = $this->generateMemberNo();
        }

        $name = $payload['name'];

        DB::table('users')->insert([
            'name' => $name,
            'email' => $payload['email'],
            'password' => Hash::make($password),
            'role' => $payload['role'],
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
            ->route('users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $user = DB::table('users')->where('id', $id)->first();

        if (!$user) {
            return redirect()->route('users.index');
        }

        return view('users.edit', [
            'user' => $user,
            'roles' => config('koperasi.roles'),
        ]);
    }

    public function update(Request $request, $id)
    {
        $roles = implode(',', array_keys(config('koperasi.roles')));

        $payload = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email,' . $id,
            'role' => 'required|in:' . $roles,
            'member_no' => 'nullable|string|max:30|unique:users,member_no,' . $id,
            'nip' => 'nullable|string|max:30',
            'unit_kerja' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|string|min:6|max:60',
        ]);

        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            return redirect()->route('users.index');
        }

        if ($payload['role'] === 'anggota') {
            $errors = [];
            if (empty($payload['nip'])) {
                $errors['nip'] = 'NIP wajib diisi untuk anggota.';
            }
            if (empty($payload['unit_kerja'])) {
                $errors['unit_kerja'] = 'Unit kerja wajib diisi untuk anggota.';
            }
            if (empty($payload['phone'])) {
                $errors['phone'] = 'No. HP wajib diisi untuk anggota.';
            }
            if ($errors) {
                return back()->withErrors($errors)->withInput();
            }
        }

        $memberNo = $payload['member_no'] ?? $user->member_no;
        if ($payload['role'] === 'anggota' && empty($memberNo)) {
            $memberNo = $this->generateMemberNo();
        }

        $name = $payload['name'] ?? $user->name;

        $updates = [
            'name' => $name,
            'email' => $payload['email'],
            'role' => $payload['role'],
            'member_no' => $memberNo,
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
            ->update($updates);

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil diperbarui.');
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
