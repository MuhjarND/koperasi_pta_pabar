@extends('layouts.guest')

@section('content')
    <div class="auth-card">
        <div class="auth-logo">
            <img src="{{ asset('logo_koperasi.png') }}" alt="Logo Koperasi">
        </div>
        <h1 class="auth-title">
            @include('partials.icon', ['name' => 'check'])
            Verifikasi Laporan
        </h1>

        @if($payload && ($payload['type'] ?? '') === 'laporan_koperasi_mart')
            <p class="auth-subtitle">
                Laporan Koperasi Mart ini telah ditandatangani melalui aplikasi koperasi.
            </p>
            <div class="badge success" style="margin-bottom: 16px;">Dokumen Terverifikasi</div>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Kasir</strong>
                    <span>{{ $payload['cashier'] ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <strong>Waktu Penandatanganan</strong>
                    <span>{{ $signedAt ?? '-' }}</span>
                </div>
            </div>
            <p class="auth-meta">Dokumen ini ditandatangani secara digital melalui Sistem Koperasi Digital.</p>
        @else
            <p class="auth-subtitle">Laporan tidak ditemukan atau belum ditandatangani.</p>
        @endif
    </div>
@endsection
