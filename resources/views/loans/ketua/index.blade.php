@extends('layouts.app')

@section('title', 'Persetujuan Ketua')
@section('subtitle', 'Pengajuan yang sudah disetujui bendahara.')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'star'])</div>
                <h3>Daftar Persetujuan</h3>
            </div>
        </div>
        @include('loans.partials.approval_history_table', [
            'loans' => $loans,
            'showAction' => true,
            'actionRoute' => 'ketua.loans.show',
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
            'actionRoute' => 'ketua.loans.show',
        ])
    </div>
@endsection

