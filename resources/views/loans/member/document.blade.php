@extends('layouts.app')

@section('title', 'Dokumen Pencairan Pinjaman')
@section('subtitle', 'Form pengajuan dan bukti transfer pinjaman Anda.')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'file'])</div>
                <h3>Ringkasan Pinjaman</h3>
            </div>
        </div>
        <div class="info-grid">
            <div class="info-item">
                <span>Nominal</span>
                <strong>Rp {{ number_format($loan->amount, 2, ',', '.') }}</strong>
            </div>
            <div class="info-item">
                <span>Tenor</span>
                <strong>{{ $loan->term_months }} bulan</strong>
            </div>
            <div class="info-item">
                <span>Status</span>
                <strong>{{ $statusLabels[$loan->status] ?? $loan->status }}</strong>
            </div>
            <div class="info-item">
                <span>Tanggal</span>
                <strong>{{ $loan->created_at }}</strong>
            </div>
        </div>
    </div>

    <div class="grid-two" style="margin-top: 16px;">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'file'])</div>
                    <h3>Form Pengajuan</h3>
                </div>
            </div>
            @if($loan->pdf_path)
                <iframe class="pdf-preview" src="{{ asset($loan->pdf_path) }}" title="Form Pengajuan"></iframe>
            @else
                <div class="muted">Form pengajuan belum tersedia.</div>
            @endif
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'wallet'])</div>
                    <h3>Bukti Transfer</h3>
                </div>
            </div>
            @php
                $evidencePath = $loan->transfer_evidence_path;
                $evidenceExt = $evidencePath ? strtolower(pathinfo($evidencePath, PATHINFO_EXTENSION)) : '';
                $isPdfEvidence = $evidenceExt === 'pdf';
            @endphp
            @if($evidencePath)
                @if($isPdfEvidence)
                    <iframe class="pdf-preview" src="{{ asset($evidencePath) }}" title="Bukti Transfer"></iframe>
                @else
                    <img src="{{ asset($evidencePath) }}" alt="Bukti Transfer" style="width: 100%; border-radius: 16px; border: 1px solid var(--stroke);">
                @endif
            @else
                <div class="muted">Bukti transfer belum tersedia.</div>
            @endif
        </div>
    </div>
@endsection
