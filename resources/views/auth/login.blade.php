@extends('layouts.guest')

@section('content')
    <div class="auth-card">
        <div class="auth-logo">
            <img src="{{ asset('logo_koperasi.png') }}" alt="Logo Koperasi">
        </div>
        <h1 class="auth-title">
            @include('partials.icon', ['name' => 'user'])
            Masuk Koperasi Digital
        </h1>
        <p class="auth-subtitle">Kelola pinjaman dengan alur persetujuan yang jelas.</p>

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
                <input type="password" name="password" placeholder="********" required>
            </div>
            <button class="btn btn-primary" type="submit">Masuk</button>
        </form>

        <div class="auth-meta">
            Gunakan akun demo: superadmin@koperasi.test / koperasi123
        </div>
    </div>
@endsection
