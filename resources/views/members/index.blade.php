@extends('layouts.app')

@section('title', 'Manajemen Anggota')
@section('subtitle', 'Kelola data anggota dan status keaktifannya.')

@section('content')
    <div class="action-row" style="margin-bottom: 16px;">
        <a class="btn btn-primary" href="{{ route('members.create') }}">Tambah Anggota</a>
        <form method="get" action="{{ route('members.index') }}" class="action-row">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama/email/no anggota">
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
                <h3>Daftar Anggota</h3>
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>No Anggota</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    <tr>
                        <td>{{ $member->member_no ?? '-' }}</td>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->email }}</td>
                        <td>{{ $member->status === 'active' ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>
                            <a class="btn btn-ghost" href="{{ route('members.edit', $member->id) }}">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Belum ada anggota.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
