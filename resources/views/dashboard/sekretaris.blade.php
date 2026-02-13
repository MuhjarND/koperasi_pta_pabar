@extends('layouts.app')

@section('title', 'Dashboard Sekretaris')
@section('subtitle', 'Review awal pengajuan pinjaman dari anggota.')

@section('content')
    <div class="stat-grid">
        <div class="stat stat--balance">
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
                @include('partials.icon', ['name' => 'clipboard'])
            </div>
            <div>
                <div>Pengajuan Menunggu Review</div>
                <div class="value">{{ $pendingCount }}</div>
            </div>
        </div>
        <div class="stat">
            <div class="stat-icon">
                @include('partials.icon', ['name' => 'check'])
            </div>
            <div>
                <div>Verifikasi Kas Menunggu</div>
                <div class="value">{{ $pendingCashCount }}</div>
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
                <h3>Antrean Review</h3>
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

    <div class="card" style="margin-top: 16px;">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'check'])</div>
                <h3>Menunggu Verifikasi Pemasukan/Pengeluaran</h3>
            </div>
            <a class="btn btn-ghost" href="{{ route('saldo.index') }}">Buka Saldo</a>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Uraian</th>
                    <th>Jumlah</th>
                    <th>Input Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingCashEntries as $entry)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($entry->entry_date)->format('d/m/Y') }}</td>
                        <td>{{ $entry->direction === 'in' ? 'Pemasukan' : 'Pengeluaran' }}</td>
                        <td>{{ $entry->description }}</td>
                        <td>Rp {{ number_format($entry->amount ?? 0, 2, ',', '.') }}</td>
                        <td>{{ $entry->created_by_name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Tidak ada data yang menunggu verifikasi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

