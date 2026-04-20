@extends('layouts.guest')

@section('content')
    <div class="auth-card auth-card--login">
        <h1 class="auth-title">
            @include('partials.icon', ['name' => 'user'])
            Masuk Koperasi Digital
        </h1>
        <p class="auth-subtitle">Solusi koperasi modern untuk kemudahan simpan pinjam yang cepat, aman, dan teratur.</p>

        @if($errors->any())
            <div class="alert danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('login.submit') }}" class="form-grid">
            @csrf
            <div class="form-control">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@koperasi.test" required>
            </div>
            <div class="form-control">
                <label>Password</label>
                <div class="password-field">
                    <input type="password" name="password" placeholder="********" required>
                    <button type="button" class="password-toggle" aria-label="Tampilkan password">
                        @include('partials.icon', ['name' => 'eye'])
                    </button>
                </div>
            </div>
            <label class="checkbox">
                <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <span>Ingat saya (2 bulan)</span>
            </label>
            <button class="btn btn-primary" type="submit">Masuk</button>
        </form>

    </div>
@endsection
