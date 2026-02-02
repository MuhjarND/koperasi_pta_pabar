@extends('layouts.guest')

@section('content')
    <div class="auth-card">
        <div class="auth-logo">
            <img src="{{ asset('logo_koperasi.png') }}" alt="Logo Koperasi">
        </div>
        <h1 class="auth-title">
            @include('partials.icon', ['name' => 'user'])
            Pendaftaran Anggota
        </h1>

        @if($error)
            <div class="alert danger">{{ $error }}</div>
        @else
            @if($errors->any())
                <div class="alert danger">{{ $errors->first() }}</div>
            @endif

            <form method="post" action="{{ route('public.register.submit', $invite->token) }}" class="form-grid">
                @csrf
                <div class="form-control">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email', $invite->email ?? '') }}" {{ $invite->email ? 'readonly' : '' }} required>
                </div>
                <div class="form-control">
                    <label>Alamat</label>
                    <textarea name="address">{{ old('address') }}</textarea>
                </div>
                <p class="muted">Nomor anggota dibuat otomatis sesuai urutan pendaftaran.</p>
                <div class="form-control">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-control">
                    <label>Ulangi Password</label>
                    <input type="password" name="password_confirmation" required>
                </div>
                <button class="btn btn-primary" type="submit">Daftar</button>
            </form>
        @endif
    </div>
@endsection
