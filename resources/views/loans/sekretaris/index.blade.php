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
        <table class="table">
            <thead>
                <tr>
                    <th>Anggota</th>
                    <th>Nominal</th>
                    <th>Tenor</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                    <tr>
                        <td>{{ $loan->name }}</td>
                        <td>Rp {{ number_format($loan->amount, 2, ',', '.') }}</td>
                        <td>{{ $loan->term_months }} bulan</td>
                        <td>{{ $loan->created_at }}</td>
                        <td>
                            <a class="btn btn-ghost" href="{{ route('sekretaris.loans.show', $loan->id) }}">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Tidak ada pengajuan yang menunggu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

