@extends('layouts.app')

@section('title', 'Dashboard Super Admin')
@section('subtitle', 'Pantau keseluruhan aktivitas koperasi dalam satu layar.')

@section('content')
    @php
        $badgeClass = config('koperasi.status_badges');
        $roleIcons = [
            'superadmin' => 'users',
            'sekretaris' => 'clipboard',
            'bendahara' => 'wallet',
            'bendahara_kantor' => 'check',
            'ketua' => 'star',
            'anggota' => 'user',
        ];
    @endphp

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
        @foreach($roleLabels as $key => $label)
            <div class="stat">
                <div class="stat-icon">
                    @include('partials.icon', ['name' => $roleIcons[$key] ?? 'user'])
                </div>
                <div>
                    <div>{{ $label }}</div>
                    <div class="value">{{ $roleCounts[$key] ?? 0 }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid-two">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'users'])</div>
                    <h3>Akses Dashboard Role</h3>
                </div>
            </div>
            <div class="action-row">
                <a class="btn btn-ghost" href="{{ route('dashboard.sekretaris') }}">Dashboard Sekretaris</a>
                <a class="btn btn-ghost" href="{{ route('dashboard.bendahara') }}">Dashboard Bendahara</a>
                <a class="btn btn-ghost" href="{{ route('dashboard.bendahara_kantor') }}">Dashboard Bendahara Kantor</a>
                <a class="btn btn-ghost" href="{{ route('dashboard.ketua') }}">Dashboard Ketua</a>
                <a class="btn btn-ghost" href="{{ route('dashboard.anggota') }}">Dashboard Anggota</a>
                <a class="btn btn-primary" href="{{ route('anggota.loans.create') }}">Ajukan Peminjaman</a>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'chart'])</div>
                    <h3>Status Peminjaman</h3>
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($statusLabels as $status => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ $loanCounts[$status] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'history'])</div>
                    <h3>Pengajuan Terbaru</h3>
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th>Nominal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentLoans as $loan)
                        <tr>
                            <td>{{ $loan->name }}</td>
                            <td>Rp {{ number_format($loan->amount, 2, ',', '.') }}</td>
                            <td>
                                <span class="badge {{ $badgeClass[$loan->status] ?? '' }}">
                                    {{ $statusLabels[$loan->status] ?? $loan->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">Belum ada pengajuan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

