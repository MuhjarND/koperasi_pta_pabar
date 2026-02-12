@extends('layouts.app')

@section('title', 'Penjualan Koperasi')
@section('subtitle', 'Catat transaksi penjualan barang koperasi.')

@section('content')
    <div class="action-row" style="margin-bottom: 16px;">
        <a class="btn btn-primary" href="{{ route('sales.create') }}">Transaksi Baru</a>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'cart'])</div>
                <h3>Riwayat Penjualan</h3>
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pembeli</th>
                    <th>Item Dibeli</th>
                    <th>Jumlah Dibeli</th>
                    <th>Total</th>
                    <th>Laba</th>
                    <th>Kasir</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr>
                        <td>#{{ $sale->id }}</td>
                        <td>{{ $sale->buyer_name ?? '-' }}</td>
                        <td>{{ $sale->items_summary ?? '-' }}</td>
                        <td>{{ (int) ($sale->total_qty ?? 0) }}</td>
                        <td>Rp {{ number_format($sale->total_amount, 2, ',', '.') }}</td>
                        <td>Rp {{ number_format($sale->profit_amount ?? 0, 2, ',', '.') }}</td>
                        <td>{{ $sale->cashier_name ?? '-' }}</td>
                        <td>{{ $sale->created_at }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">Belum ada transaksi penjualan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

