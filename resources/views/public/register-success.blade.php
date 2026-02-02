@extends('layouts.guest')

@section('content')
    <div class="auth-card">
        <div class="auth-logo">
            <img src="{{ asset('logo_koperasi.png') }}" alt="Logo Koperasi">
        </div>
        <h1 class="auth-title">
            @include('partials.icon', ['name' => 'check'])
            Pendaftaran Berhasil
        </h1>
        <p class="auth-subtitle">Akun Anda sudah aktif. Silakan hubungi sekretaris jika membutuhkan bantuan.</p>
        <a class="btn btn-primary" href="{{ route('login') }}">Ke Halaman Login</a>
    </div>
@endsection
