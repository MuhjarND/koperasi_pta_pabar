@extends('layouts.app')

@section('title', 'Verifikasi Pelunasan')
@section('subtitle', 'Pelunasan yang diinput bendahara menunggu verifikasi sekretaris.')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'check'])</div>
                <h3>Daftar Pelunasan Menunggu Verifikasi</h3>
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Anggota</th>
                    <th>Tanggal</th>
                    <th>Nominal</th>
                    <th>Diinput Oleh</th>
                    <th>Eviden</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($settlements as $row)
                    <tr>
                        <td>
                            {{ $row->anggota_name }}
                            <div class="muted">{{ $row->member_no ?? '-' }}</div>
                        </td>
                        <td>{{ $row->paid_at }}</td>
                        <td>Rp {{ number_format((float) $row->amount_principal + (float) $row->amount_fee, 2, ',', '.') }}</td>
                        <td>{{ $row->creator_name ?? '-' }}</td>
                        <td>
                            @if(!empty($row->evidence_path))
                                <a class="link-short" href="{{ asset($row->evidence_path) }}" target="_blank" rel="noopener">Lihat</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <form method="post" action="{{ route('sekretaris.loans.settlements.approve', $row->id) }}">
                                @csrf
                                <button class="btn btn-primary" type="submit">Validasi</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Tidak ada pelunasan yang menunggu verifikasi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
