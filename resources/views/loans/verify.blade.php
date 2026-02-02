@extends('layouts.guest')

@section('content')
    <div class="auth-card">
        <div class="auth-logo">
            <img src="{{ asset('logo_koperasi.png') }}" alt="Logo Koperasi">
        </div>
        <h1 class="auth-title">
            @include('partials.icon', ['name' => 'check'])
            Verifikasi Formulir
        </h1>

        @if($loan)
            <p class="auth-subtitle">
                Formulir pinjaman ini telah ditandatangani melalui aplikasi koperasi.
                @if(!empty($roleLabel))
                    <br><strong>Verifikasi:</strong> {{ $roleLabel }}
                @endif
            </p>
            <div class="badge success" style="margin-bottom: 16px;">Dokumen Terverifikasi</div>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Nama Anggota</strong>
                    <span>{{ $loan->applicant_name ?? $loan->user_name ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <strong>Nomor Anggota</strong>
                    <span>{{ $loan->member_no ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <strong>Tanggal Pengajuan</strong>
                    <span>{{ $loan->created_at ? \Carbon\Carbon::parse($loan->created_at)->locale('id')->translatedFormat('d F Y') : '-' }}</span>
                </div>
                <div class="info-item">
                    <strong>Waktu Penandatanganan</strong>
                    <span>{{ $signedAt ?? '-' }}</span>
                </div>
            </div>
            <p class="auth-meta">Dokumen ini ditandatangani secara digital melalui Sistem Koperasi Digital.</p>
        @else
            <p class="auth-subtitle">Formulir tidak ditemukan atau belum ditandatangani.</p>
        @endif
    </div>
@endsection
