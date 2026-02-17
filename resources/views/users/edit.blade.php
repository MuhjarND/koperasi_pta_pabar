@extends('layouts.app')

@section('title', 'Edit User')
@section('subtitle', 'Perbarui profil akun pengguna.')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'users'])</div>
                <h3>Profil User</h3>
            </div>
        </div>
        <form method="post" action="{{ route('users.update', $user->id) }}" class="form-grid">
            @csrf
            <div class="form-control">
                <label>Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="form-control">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="form-control">
                <label>Role</label>
                <select name="role" required>
                    @foreach($roles as $key => $label)
                        <option value="{{ $key }}" {{ old('role', $user->role) === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-control">
                <label>No Anggota (otomatis untuk anggota)</label>
                <input type="text" name="member_no" value="{{ old('member_no', $user->member_no) }}">
            </div>
            <div class="form-control">
                <label>NIP</label>
                <input type="text" name="nip" value="{{ old('nip', $user->nip) }}">
            </div>
            <div class="form-control">
                <label>Unit Kerja</label>
                <input type="text" name="unit_kerja" value="{{ old('unit_kerja', $user->unit_kerja) }}">
            </div>
            <div class="form-control">
                <label>No. HP</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}">
            </div>
            <div class="form-control">
                <label>Alamat</label>
                <textarea name="address">{{ old('address', $user->address) }}</textarea>
            </div>
            <p class="muted">Nomor anggota akan dibuat otomatis sesuai urutan pendaftaran jika dikosongkan.</p>
            <div class="form-control">
                <label>Status</label>
                <select name="status" required>
                    <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="form-control">
                <label>Password Baru (opsional)</label>
                <div class="password-field">
                    <input type="password" name="password" placeholder="Kosongkan jika tidak diubah">
                    <button type="button" class="password-toggle" aria-label="Tampilkan password">
                        @include('partials.icon', ['name' => 'eye'])
                    </button>
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
        </form>
    </div>
@endsection
