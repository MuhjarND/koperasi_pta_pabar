@extends('layouts.app')

@section('title', 'Edit Anggota')
@section('subtitle', 'Perbarui profil dan status anggota.')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'user'])</div>
                <h3>Profil Anggota</h3>
            </div>
        </div>
        <form method="post" action="{{ route('members.update', $member->id) }}" class="form-grid">
            @csrf
            <div class="form-control">
                <label>Nomor Anggota</label>
                <input type="text" name="member_no" value="{{ old('member_no', $member->member_no) }}" readonly>
            </div>
            <div class="form-control">
                <label>Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $member->name) }}" required>
            </div>
            <div class="form-control">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $member->email) }}" required>
            </div>
            <div class="form-control">
                <label>NIP</label>
                <input type="text" name="nip" value="{{ old('nip', $member->nip) }}">
            </div>
            <div class="form-control">
                <label>Unit Kerja</label>
                <input type="text" name="unit_kerja" value="{{ old('unit_kerja', $member->unit_kerja) }}">
            </div>
            <div class="form-control">
                <label>No. HP</label>
                <input type="text" name="phone" value="{{ old('phone', $member->phone) }}">
            </div>
            <div class="form-control">
                <label>Alamat</label>
                <textarea name="address">{{ old('address', $member->address) }}</textarea>
            </div>
            <div class="form-control">
                <label>Status</label>
                <select name="status" required>
                    <option value="active" {{ old('status', $member->status) === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ old('status', $member->status) === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
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
