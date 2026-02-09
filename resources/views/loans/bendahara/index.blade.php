@extends('layouts.app')

@section('title', 'Persetujuan Bendahara')
@section('subtitle', 'Pengajuan yang sudah diverifikasi sekretaris.')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'check'])</div>
                <h3>Daftar Persetujuan</h3>
            </div>
        </div>
        @include('loans.partials.approval_history_table', [
            'loans' => $loans,
            'showAction' => true,
            'actionRoute' => 'bendahara.loans.show',
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
            'actionRoute' => 'bendahara.loans.show',
        ])
    </div>
@endsection

