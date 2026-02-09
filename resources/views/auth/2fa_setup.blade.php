@extends('layouts.app')

@section('title', 'Authenticator')
@section('subtitle', 'Aktifkan verifikasi dua langkah untuk keamanan akun.')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'user'])</div>
                <h3>Pengaturan Authenticator</h3>
            </div>
        </div>

        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert danger">{{ $errors->first() }}</div>
        @endif

        @if($enabled)
            <p class="muted">Authenticator sudah aktif untuk akun ini.</p>
            @if(!empty($recoveryCodes))
                <div class="card" style="margin-top: 16px;">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-icon">@include('partials.icon', ['name' => 'check'])</div>
                            <h3>Recovery Codes</h3>
                        </div>
                    </div>
                    <p class="muted">Simpan kode berikut di tempat aman. Kode hanya tampil sekali.</p>
                    <div style="margin-top: 12px;">
                        @foreach($recoveryCodes as $code)
                            <span style="display:inline-block; padding:6px 10px; border:1px dashed #cbd5; border-radius:8px; margin:4px; font-weight:600;">
                                {{ $code }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="grid-two" style="align-items: start;">
                <div>
                    <h4>1. Scan QR Code</h4>
                    <p class="muted">Buka aplikasi Authenticator lalu scan QR berikut.</p>
                    @if($qrImage || $qrFallbackUrl)
                        <div style="max-width: 220px; margin: 12px 0;">
                            <img
                                src="{{ $qrImage ?? $qrFallbackUrl }}"
                                alt="QR Authenticator"
                                style="width: 100%; border-radius: 12px; border: 1px solid #e5e5e5;"
                                onerror="this.onerror=null; this.src='{{ $qrFallbackUrl }}';"
                            >
                        </div>
                    @else
                        <div class="muted" style="margin: 12px 0;">
                            QR tidak dapat ditampilkan. Silakan gunakan kode manual di bawah ini.
                        </div>
                    @endif
                    @if($manualKey)
                        <div class="muted">Kode manual: <strong>{{ $manualKey }}</strong></div>
                    @endif
                </div>
                <div>
                    <h4>2. Masukkan Kode 6 Digit</h4>
                    <p class="muted">Masukkan kode yang muncul di aplikasi Authenticator.</p>
                    <form method="post" action="{{ route('authenticator.enable') }}" class="form-grid">
                        @csrf
                        <div class="form-control">
                            <label>Kode Autentikasi</label>
                            <input type="text" name="otp" placeholder="Contoh: 123456" required>
                        </div>
                        <button class="btn btn-primary" type="submit">Aktifkan Authenticator</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
