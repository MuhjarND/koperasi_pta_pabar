@extends('layouts.app')

@section('title', 'Manajemen User')
@section('subtitle', 'Kelola akun semua peran di koperasi.')

@section('content')
    <div class="action-row" style="margin-bottom: 16px;">
        <a class="btn btn-primary" href="{{ route('users.create') }}">Tambah User</a>
        <form method="post" action="{{ route('users.credentials.sendAll') }}" onsubmit="return confirm('Kirim ulang username dan password baru ke seluruh anggota aktif? Password anggota akan diperbarui.');">
            @csrf
            <button class="btn btn-ghost" type="submit">
                @include('partials.icon', ['name' => 'users'])
                Kirim ke Seluruh Anggota
            </button>
        </form>
        <form method="get" action="{{ route('users.index') }}" class="action-row">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama/email/no anggota">
            <select name="role">
                <option value="">Semua Role</option>
                @foreach($roles as $key => $label)
                    <option value="{{ $key }}" {{ ($filters['role'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">Semua Status</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'users'])</div>
                <h3>Daftar User</h3>
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>No Anggota</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $roles[$user->role] ?? $user->role }}</td>
                        <td>{{ $user->member_no ?? '-' }}</td>
                        <td>{{ $user->status === 'active' ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>
                            <div class="action-row">
                                <a class="btn btn-ghost" href="{{ route('users.edit', $user->id) }}">Edit</a>
                                @if($user->role === 'anggota')
                                    <form method="post" action="{{ route('users.credentials.send', $user->id) }}" onsubmit="return confirm('Kirim username dan password baru ke {{ $user->name }}? Password anggota akan diperbarui.');">
                                        @csrf
                                        <button class="btn btn-ghost" type="submit">
                                            @include('partials.icon', ['name' => 'barcode'])
                                            Kirim Login
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada user.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
