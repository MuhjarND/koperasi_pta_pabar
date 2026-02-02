@extends('layouts.app')

@section('title', 'Peminjaman Saya')
@section('subtitle', 'Daftar pengajuan pinjaman yang pernah Anda ajukan.')

@section('content')
    @php
        $badgeClass = config('koperasi.status_badges');
    @endphp

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
                    <th>Form PDF</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                    <tr>
                        <td>Rp {{ number_format($loan->amount, 2, ',', '.') }}</td>
                        <td>{{ $loan->term_months }} bulan</td>
                        <td>
                            <span class="badge {{ $badgeClass[$loan->status] ?? '' }}">
                                {{ $statusLabels[$loan->status] ?? $loan->status }}
                            </span>
                        </td>
                        <td>{{ $loan->created_at }}</td>
                        <td>
                            @if($loan->status === 'approved_chairman' && $loan->pdf_path)
                                <a class="btn btn-ghost" href="{{ asset($loan->pdf_path) }}" target="_blank" rel="noopener">Lihat PDF</a>
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

