@extends('layouts.app')

@section('title', 'Peminjaman Saya')
@section('subtitle', 'Daftar pengajuan pinjaman yang pernah Anda ajukan.')

@section('content')
    @php
        $badgeClass = config('koperasi.status_badges');
    @endphp

    <div class="summary-grid" style="margin-bottom: 16px;">
        <div class="summary-item accent">
            <div class="label">Total Pengajuan</div>
            <div class="value">{{ $loanSummary['total'] ?? 0 }} pengajuan</div>
        </div>
        <div class="summary-item">
            <div class="label">Disetujui</div>
            <div class="value">Rp {{ number_format($loanSummary['approved_amount'] ?? 0, 2, ',', '.') }}</div>
        </div>
        <div class="summary-item warning">
            <div class="label">Menunggu</div>
            <div class="value">{{ $loanSummary['pending'] ?? 0 }} pengajuan</div>
        </div>
        <div class="summary-item">
            <div class="label">Ditolak</div>
            <div class="value">{{ $loanSummary['rejected'] ?? 0 }} pengajuan</div>
        </div>
    </div>

    <div class="action-row" style="margin-bottom: 16px;">
        <a class="btn btn-primary" href="{{ route('anggota.loans.create') }}">Ajukan Pinjaman</a>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'file'])</div>
                <h3>Riwayat Peminjaman</h3>
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Nominal</th>
                    <th>Tenor</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Dokumen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                    <tr>
                        <td data-label="Nominal">Rp {{ number_format($loan->amount, 2, ',', '.') }}</td>
                        <td data-label="Tenor">{{ $loan->term_months }} bulan</td>
                        <td data-label="Status">
                            <span class="badge {{ $badgeClass[$loan->status] ?? '' }}">
                                {{ $statusLabels[$loan->status] ?? $loan->status }}
                            </span>
                        </td>
                        <td data-label="Tanggal">{{ $loan->created_at }}</td>
                        <td data-label="Dokumen">
                            @if($loan->status === 'approved_chairman' && $loan->transfer_evidence_path)
                                <a class="btn btn-ghost" href="{{ route('anggota.loans.document', $loan->id) }}">Lihat Dokumen</a>
                            @elseif($loan->status === 'approved_chairman')
                                <span class="muted">Menunggu pencairan</span>
                            @else
                                <span class="muted">Belum tersedia</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Belum ada pengajuan pinjaman.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

