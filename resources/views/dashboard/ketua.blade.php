@extends('layouts.app')

@section('title', 'Dashboard Ketua')
@section('subtitle', 'Persetujuan akhir sebelum dana dicairkan.')

@section('content')
    <div class="stat-grid">
        <div class="stat">
            <div class="stat-icon">
                @include('partials.icon', ['name' => 'wallet'])
            </div>
            <div>
                <div>Saldo Koperasi</div>
                <div class="value">Rp {{ number_format($balance, 2, ',', '.') }}</div>
            </div>
        </div>
        <div class="stat">
            <div class="stat-icon">
                @include('partials.icon', ['name' => 'star'])
            </div>
            <div>
                <div>Pengajuan Menunggu Persetujuan Akhir</div>
                <div class="value">{{ $pendingCount }}</div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top: 16px;">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'plus'])</div>
                <h3>Aksi Cepat</h3>
            </div>
            <a class="btn btn-primary" href="{{ route('anggota.loans.create') }}">Ajukan Peminjaman</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'history'])</div>
                <h3>Antrean Persetujuan Akhir</h3>
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Anggota</th>
                    <th>Nominal</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($queue as $loan)
                    <tr>
                        <td>{{ $loan->name }}</td>
                        <td>Rp {{ number_format($loan->amount, 2, ',', '.') }}</td>
                        <td>{{ $loan->created_at }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Belum ada antrean.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

