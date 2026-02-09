@extends('layouts.app')

@section('title', 'Review Peminjaman')
@section('subtitle', 'Pengajuan baru yang menunggu verifikasi dokumen.')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'clipboard'])</div>
                <h3>Daftar Review</h3>
            </div>
        </div>
        @include('loans.partials.approval_history_table', [
            'loans' => $loans,
            'showAction' => true,
            'actionRoute' => 'sekretaris.loans.show',
        ])
    </div>

    <div style="height: 16px;"></div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'history'])</div>
                <h3>Riwayat Persetujuan Terakhir</h3>
            </div>
        </div>
        @include('loans.partials.approval_history_table', [
            'loans' => $historyLoans,
            'showAction' => true,
            'actionRoute' => 'sekretaris.loans.show',
        ])
    </div>
@endsection

