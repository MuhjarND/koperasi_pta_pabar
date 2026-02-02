@extends('layouts.app')

@section('title', 'Link Pendaftaran Anggota')
@section('subtitle', 'Buat link khusus untuk pendaftaran anggota baru.')

@section('content')
    <div class="grid-two">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'plus'])</div>
                    <h3>Buat Link Pendaftaran</h3>
                </div>
            </div>
            <form method="post" action="{{ route('invites.store') }}" class="form-grid">
                @csrf
                <div class="form-control">
                    <label>Nomor Anggota (opsional)</label>
                    <input type="text" name="member_no" value="{{ old('member_no') }}" placeholder="Mis. A-010">
                </div>
                <div class="form-control">
                    <label>Email (opsional)</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@koperasi.test">
                </div>
                <div class="form-control">
                    <label>Masa Berlaku (hari)</label>
                    <input type="number" name="expires_in" min="1" max="30" value="{{ old('expires_in', 7) }}">
                </div>
                <div class="form-control">
                    <label>Catatan</label>
                    <textarea name="note" placeholder="Opsional">{{ old('note') }}</textarea>
                </div>
                <button class="btn btn-primary" type="submit">Buat Link</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'history'])</div>
                    <h3>Riwayat Link</h3>
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Link Pendaftaran</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invites as $invite)
                        @php
                            $isExpired = $invite->expires_at && now()->greaterThan($invite->expires_at);
                            $status = $invite->used_at ? 'Sudah digunakan' : ($isExpired ? 'Kadaluarsa' : 'Aktif');
                            $link = url('/daftar/' . $invite->token);
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ $link }}" class="link-short" title="{{ $link }}">{{ \Illuminate\Support\Str::limit($link, 55) }}</a>
                                <small class="muted">Dibuat: {{ $invite->created_at }}</small>
                                <div class="invite-meta">
                                    <span>Status: {{ $status }}</span>
                                    <span>Member No: {{ $invite->member_no ?? '-' }}</span>
                                    <span>Email: {{ $invite->email ?? '-' }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td>Belum ada link pendaftaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
