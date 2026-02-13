@extends('layouts.app')

@section('title', 'Dashboard Bendahara Kantor')
@section('subtitle', 'Verifikasi pemotongan gaji sebelum masuk saldo koperasi.')

@section('content')
    <div class="grid-two">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'wallet'])</div>
                    <h3>Saldo Koperasi</h3>
                </div>
            </div>
            <div class="stat stat--balance">
                <div class="stat-icon">
                    @include('partials.icon', ['name' => 'wallet'])
                </div>
                <div>
                    <div>Saldo Terkini</div>
                    <div class="value">Rp {{ number_format($balance ?? 0, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'clipboard'])</div>
                    <h3>Menunggu Verifikasi</h3>
                </div>
            </div>
            <div class="stat">
                <div class="stat-icon">
                    @include('partials.icon', ['name' => 'clipboard'])
                </div>
                <div>
                    <div>Jumlah Pemotongan</div>
                    <div class="value">{{ $pendingVerifications ?? 0 }}</div>
                </div>
            </div>
            <p class="muted">Verifikasi pemotongan agar masuk ke saldo koperasi.</p>
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

    <div class="card" style="margin-top: 16px;">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'history'])</div>
                <h3>Daftar Pemotongan Terbaru</h3>
            </div>
            <a class="btn btn-ghost" href="{{ route('deductions.index') }}">Lihat Semua</a>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Tanggal</th>
                    <th>Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($latestVerifications as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->processed_at ? \Carbon\Carbon::parse($row->processed_at)->format('d/m/Y') : '-' }}</td>
                        <td>Rp {{ number_format($row->total_amount ?? 0, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Belum ada pemotongan menunggu verifikasi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

