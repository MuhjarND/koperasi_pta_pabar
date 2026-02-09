@extends('layouts.app')

@section('title', 'Detail Pengajuan')
@section('subtitle', 'Persetujuan akhir untuk pengajuan pinjaman.')

@section('content')
    @php
        $feeRate = config('koperasi.service_fee_rate');
        $serviceFee = $loan->amount * $feeRate;
        $installment = $loan->term_months ? ($loan->amount / $loan->term_months) + $serviceFee : 0;
    @endphp

    <div class="grid-two">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'user'])</div>
                    <h3>Profil Anggota</h3>
                </div>
            </div>
            <p><strong>Nomor Anggota:</strong> {{ $loan->member_no ?? '-' }}</p>
            <p><strong>Nama:</strong> {{ $loan->anggota_name }}</p>
            <p><strong>NIP:</strong> {{ $loan->nip ?? '-' }}</p>
            <p><strong>Unit Kerja:</strong> {{ $loan->unit_kerja ?? '-' }}</p>
            <p><strong>No. HP:</strong> {{ $loan->phone ?? '-' }}</p>
            <p><strong>Selfie Validasi:</strong></p>
            @if($loan->selfie_path)
                <img class="loan-photo" src="{{ asset($loan->selfie_path) }}" alt="Selfie validasi">
            @else
                <p>-</p>
            @endif
            <p><strong>Formulir Pinjaman (PDF):</strong>
                @if($loan->pdf_path)
                    <a class="btn btn-ghost" href="{{ asset($loan->pdf_path) }}" target="_blank" rel="noopener">Lihat PDF</a>
                @else
                    <span class="muted">Belum tersedia</span>
                @endif
            </p>
            @if($loan->pdf_path)
                <iframe class="pdf-preview" src="{{ asset($loan->pdf_path) }}" title="Preview Formulir Pinjaman"></iframe>
            @endif
            <p><strong>Nominal:</strong> Rp {{ number_format($loan->amount, 2, ',', '.') }}</p>
            <p><strong>Tenor:</strong> {{ $loan->term_months }} bulan</p>
            <p><strong>Tujuan:</strong> {{ $loan->purpose }}</p>
            <p><strong>Jasa per Bulan:</strong> Rp {{ number_format($serviceFee, 2, ',', '.') }}</p>
            <p><strong>Angsuran per Bulan:</strong> Rp {{ number_format($installment, 2, ',', '.') }}</p>
            <p><strong>Catatan Sekretaris:</strong> {{ $loan->sekretaris_note ?? '-' }}</p>
            <p><strong>Catatan Bendahara:</strong> {{ $loan->bendahara_note ?? '-' }}</p>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'star'])</div>
                    <h3>Keputusan Ketua</h3>
                </div>
            </div>
            <form method="post" action="{{ route('ketua.loans.approve', $loan->id) }}" class="form-grid">
                @csrf
                <div class="form-control">
                    <label>Keputusan</label>
                    <select name="decision" required>
                        <option value="approve">Setujui Pengajuan</option>
                        <option value="reject">Tolak</option>
                    </select>
                </div>
                <div class="form-control">
                    <label>Catatan (opsional)</label>
                    <textarea name="note" placeholder="Tambahkan catatan ketua"></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Simpan Keputusan</button>
            </form>
        </div>
    </div>

    @include('loans.partials.approval_history', ['loan' => $loan])
@endsection

