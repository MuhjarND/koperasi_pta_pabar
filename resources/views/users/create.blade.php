@extends('layouts.app')

@section('title', 'Tambah User')
@section('subtitle', 'Buat akun baru untuk peran koperasi.')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'users'])</div>
                <h3>Profil User</h3>
            </div>
        </div>
        <form method="post" action="{{ route('users.store') }}" class="form-grid">
            @csrf
            <div class="form-control">
                <label>Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="form-control">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="form-control">
                <label>Role</label>
                <select name="role" required>
                    @foreach($roles as $key => $label)
                        <option value="{{ $key }}" {{ old('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-control">
                <label>No Anggota (otomatis untuk anggota)</label>
                <input type="text" name="member_no" value="{{ old('member_no') }}">
            </div>
            <div class="form-control">
                <label>NIP</label>
                <input type="text" name="nip" value="{{ old('nip') }}">
            </div>
            <div class="form-control">
                <label>Unit Kerja</label>
                <input type="text" name="unit_kerja" value="{{ old('unit_kerja') }}">
            </div>
            <div class="form-control">
                <label>No. HP</label>
                <input type="text" name="phone" value="{{ old('phone') }}">
            </div>
            <div class="form-control">
                <label>Alamat</label>
                <textarea name="address">{{ old('address') }}</textarea>
            </div>
            <p class="muted">Nomor anggota akan dibuat otomatis sesuai urutan pendaftaran jika dikosongkan.</p>
            <div class="form-control">
                <label>Status</label>
                <select name="status" required>
                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="form-control">
                <label>Password (opsional)</label>
                <input type="password" name="password" placeholder="Default: koperasi123">
            </div>
            <button class="btn btn-primary" type="submit">Simpan</button>
        </form>
    </div>
@endsection
