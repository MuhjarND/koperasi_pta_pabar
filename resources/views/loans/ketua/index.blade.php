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
                            <a class="btn btn-ghost" href="{{ route('ketua.loans.show', $loan->id) }}">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Tidak ada pengajuan untuk disetujui.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

