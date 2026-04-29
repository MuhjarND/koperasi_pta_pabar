@extends('layouts.guest')

@section('body_class', 'login-split-page')

@push('styles')
    <style>
        body.login-split-page {
            margin: 0;
            background: #ffffff;
        }
        body.login-split-page .auth-wrapper {
            min-height: 100vh;
            padding: 0 !important;
            display: block !important;
            background: #ffffff !important;
        }
        .login-split {
            width: 100%;
            min-height: 100vh;
            display: grid !important;
            grid-template-columns: minmax(0, 1.15fr) minmax(420px, 0.85fr);
            background: #ffffff;
        }
        .login-brand-panel {
            min-height: 100vh;
            display: grid !important;
            place-items: center;
            padding: 56px 48px;
            color: #ffffff;
            background:
                radial-gradient(circle at 50% 58%, rgba(245,230,31,0.18), transparent 32%),
                linear-gradient(135deg, #005c2d 0%, #009848 58%, #e02028 100%);
        }
        .login-brand-content {
            width: min(440px, 100%);
            display: grid;
            justify-items: center;
            gap: 12px;
            text-align: center;
        }
        .login-brand-mark {
            width: 88px !important;
            height: 88px !important;
            border-radius: 24px;
            display: grid;
            place-items: center;
            background: rgba(17,18,13,0.16);
            border: 1px solid rgba(245,230,31,0.62);
            box-shadow: 0 24px 80px rgba(0,0,0,0.22);
            overflow: hidden;
        }
        .login-brand-mark img {
            width: 62px !important;
            height: 62px !important;
            max-width: 62px !important;
            max-height: 62px !important;
            object-fit: contain;
            display: block;
        }
        .login-brand-content h1 {
            margin: 16px 0 0;
            font-size: clamp(30px, 3vw, 40px);
            line-height: 1.1;
            letter-spacing: -0.04em;
            font-weight: 800;
        }
        .login-brand-content p {
            margin: 0;
            color: rgba(255,255,255,0.78);
            font-size: 16px;
        }
        .login-divider {
            width: 74px;
            height: 3px;
            border-radius: 999px;
            background: linear-gradient(90deg, #f5e61f, #ffffff, #e02028);
            margin: 8px 0 12px;
        }
        .login-brand-copy {
            max-width: 370px;
            line-height: 1.7;
        }
        .login-form-panel {
            min-height: 100vh;
            display: grid !important;
            place-items: center;
            padding: 56px clamp(28px, 5vw, 76px);
            background: #ffffff;
        }
        .login-form-card {
            width: min(480px, 100%);
            display: grid;
            gap: 0;
        }
        .login-form-card h2 {
            margin: 0 0 4px;
            color: #009848;
            font-size: 28px;
            line-height: 1.2;
            letter-spacing: -0.03em;
            font-weight: 800;
        }
        .login-input-group {
            min-height: 56px;
            width: 100%;
            display: grid !important;
            grid-template-columns: 58px minmax(0, 1fr);
            align-items: center;
            border: 1px solid #dbe3ec;
            border-radius: 9px;
            background: #ffffff;
            overflow: hidden;
        }
        .login-input-group:focus-within {
            border-color: #009848;
            box-shadow: 0 0 0 3px rgba(0,152,72,0.12);
        }
        .login-input-group > span {
            height: 100%;
            display: grid;
            place-items: center;
            color: #94a3b8;
            background: #f8fafc;
            border-right: 1px solid #dbe3ec;
        }
        .login-input-group input {
            width: 100%;
            height: 100%;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            padding: 0 16px !important;
            background: #ffffff;
        }
        .password-field.login-input-group {
            grid-template-columns: 58px minmax(0, 1fr) 44px;
        }
        .password-field.login-input-group .password-toggle {
            position: static !important;
            transform: none !important;
            width: 44px !important;
            height: 100% !important;
            border: 0 !important;
            border-left: 1px solid #dbe3ec !important;
            border-radius: 0 !important;
            background: #ffffff !important;
        }
        .login-submit {
            min-height: 56px;
            border-radius: 9px;
            background: #009848 !important;
            border-color: #009848 !important;
            color: #ffffff !important;
            font-size: 16px;
            font-weight: 800;
        }
        .login-submit:hover {
            background: #00783a !important;
            border-color: #00783a !important;
        }
        @media (max-width: 820px) {
            .login-split {
                grid-template-columns: 1fr;
            }
            .login-brand-panel {
                min-height: 34vh;
                padding: 34px 20px;
            }
            .login-brand-mark {
                width: 72px !important;
                height: 72px !important;
            }
            .login-brand-mark img {
                width: 52px !important;
                height: 52px !important;
            }
            .login-brand-content h1 {
                font-size: 28px;
            }
            .login-brand-copy {
                display: none;
            }
            .login-form-panel {
                min-height: auto;
                padding: 30px 20px 44px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="login-split">
        <section class="login-brand-panel" aria-label="Koperasi Digital">
            <div class="login-brand-content">
                <div class="login-brand-mark">
                    <img src="{{ asset('logo_koperasi.png') }}" alt="Logo Koperasi">
                </div>
                <h1>Koperasi Digital</h1>
                <p>Koperasi As-Salam PTA Papua Barat</p>
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
