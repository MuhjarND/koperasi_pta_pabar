@extends('layouts.app')

@section('title', 'Pencairan Pinjaman')
@section('subtitle', 'Unggah bukti transfer untuk pinjaman yang sudah disetujui ketua.')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'wallet'])</div>
                <h3>Daftar Pencairan</h3>
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Anggota</th>
                    <th>Nominal</th>
                    <th>Tenor</th>
                    <th>Tanggal Disetujui</th>
                    <th>Form</th>
                    <th>Bukti Transfer</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                    <tr>
                        <td data-label="Anggota">
                            {{ $loan->name }}
                            @if($loan->member_no)
                                <div class="muted">{{ $loan->member_no }}</div>
                            @endif
                        </td>
                        <td data-label="Nominal">Rp {{ number_format($loan->amount, 2, ',', '.') }}</td>
                        <td data-label="Tenor">{{ $loan->term_months }} bulan</td>
                        <td data-label="Tanggal Disetujui">
                            {{ $loan->chairman_approved_at ? \Carbon\Carbon::parse($loan->chairman_approved_at)->format('d/m/Y') : '-' }}
                        </td>
                        <td data-label="Form">
                            @if($loan->pdf_path)
                                <a class="btn btn-ghost" href="{{ asset($loan->pdf_path) }}" target="_blank" rel="noopener">Lihat Form</a>
                            @else
                                <span class="muted">Belum tersedia</span>
                            @endif
                        </td>
                        <td data-label="Bukti Transfer">
                            <form method="post" action="{{ route('bendahara.loans.disbursement.store', $loan->id) }}" enctype="multipart/form-data" class="action-row">
                                @csrf
                                <input type="file" name="transfer_evidence" accept=".jpg,.jpeg,.png,.pdf" required>
                                <button class="btn btn-primary" type="submit">Unggah</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Tidak ada pinjaman yang menunggu pencairan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
