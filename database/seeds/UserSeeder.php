<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $defaultPassword = 'password';
        $timestamp = now();

        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@koperasi.test',
                'role' => 'superadmin',
                'status' => 'active',
            ],
            [
                'name' => 'Sekretaris',
                'email' => 'sekretaris@koperasi.test',
                'role' => 'sekretaris',
                'status' => 'active',
            ],
            [
                'name' => 'Bendahara',
                'email' => 'bendahara@koperasi.test',
                'role' => 'bendahara',
                'status' => 'active',
            ],
            [
                'name' => 'Ketua',
                'email' => 'ketua@koperasi.test',
                'role' => 'ketua',
                'status' => 'active',
            ],
            [
                'name' => 'Anggota',
                'email' => 'anggota@koperasi.test',
                'role' => 'anggota',
                'member_no' => 'A-001',
                'status' => 'active',
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'email_verified_at' => $timestamp,
                    'password' => Hash::make($defaultPassword),
                    'role' => $user['role'],
                    'member_no' => $user['member_no'] ?? null,
                    'status' => $user['status'] ?? 'active',
                    'remember_token' => Str::random(10),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]
            );
        }
    }
}
