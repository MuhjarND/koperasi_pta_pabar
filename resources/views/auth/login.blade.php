@extends('layouts.guest')

@section('body_class', 'login-split-page')

@section('content')
    <div class="login-split">
        <section class="login-brand-panel" aria-label="Koperasi Digital">
            <div class="login-brand-content">
                <div class="login-brand-mark">
                    <img src="{{ asset('logo_koperasi.png') }}" alt="Logo Koperasi">
                </div>
                <h1>Koperasi Digital</h1>
                <p>Koperasi Bagi Hasil As-Salam</p>
                <div class="login-divider"></div>
                <p class="login-brand-copy">Sistem simpan pinjam yang membantu anggota mengakses informasi saldo, pinjaman, dan angsuran dengan mudah.</p>
            </div>
        </section>

        <section class="login-form-panel">
            <div class="login-form-card">
                <h2>Selamat Datang</h2>
                <p class="auth-subtitle">Masuk ke akun Anda untuk melanjutkan.</p>

                @if($errors->any())
                    <div class="alert danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="post" action="{{ route('login.submit') }}" class="form-grid">
                    @csrf
                    <div class="form-control">
                        <label>Email / NIP</label>
                        <div class="login-input-group">
                            <span>@include('partials.icon', ['name' => 'barcode'])</span>
                            <input type="text" name="email" value="{{ old('email') }}" placeholder="email atau NIP" required>
                        </div>
                    </div>
                    <div class="form-control">
                        <label>Password</label>
                        <div class="password-field login-input-group">
                            <span>@include('partials.icon', ['name' => 'wallet'])</span>
                            <input type="password" name="password" placeholder="Masukkan password" required>
                            <button type="button" class="password-toggle" aria-label="Tampilkan password">
                                @include('partials.icon', ['name' => 'eye'])
                            </button>
                        </div>
                    </div>
                    <label class="checkbox login-remember">
                        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        <span>Ingat saya</span>
                    </label>
                    <button class="btn btn-primary login-submit" type="submit">Masuk</button>
                </form>
            </div>
        </section>
    </div>
@endsection
