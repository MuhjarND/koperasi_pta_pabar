@extends('layouts.guest')

@section('content')
    <div class="auth-card">
        <div class="auth-logo">
            <img src="{{ asset('logo_koperasi.png') }}" alt="Logo Koperasi">
        </div>
        <h1 class="auth-title">
            @include('partials.icon', ['name' => 'check'])
            Verifikasi Authenticator
        </h1>
        <p class="auth-subtitle">Masukkan kode autentikasi untuk melanjutkan.</p>

        @if($errors->any())
            <div class="alert danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('authenticator.verify.submit') }}" class="form-grid">
            @csrf
            <div class="form-control">
                <label>Kode Autentikasi (6 digit)</label>
                <input type="text" name="otp" placeholder="Contoh: 123456">
            </div>
            <div class="form-control">
                <label>Atau Recovery Code</label>
                <input type="text" name="recovery_code" placeholder="Contoh: ABCD-EFGH">
                <div class="muted">Gunakan recovery code jika tidak bisa membuka aplikasi authenticator.</div>
            </div>
            <button class="btn btn-primary" type="submit">Verifikasi</button>
        </form>

        <div class="auth-meta">
            Pastikan waktu di perangkat Anda sudah sinkron.
        </div>
    </div>
@endsection
