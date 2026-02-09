@extends('layouts.app')

@section('title', 'Tambah Produk')
@section('subtitle', 'Tambahkan barang baru ke inventaris koperasi.')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'box'])</div>
                <h3>Detail Produk</h3>
            </div>
        </div>
        <form method="post" action="{{ route('products.store') }}" class="form-grid">
            @csrf
            <div class="form-control">
                <label>Nama Produk</label>
                <input type="text" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="form-control">
                <label>Unit</label>
                <input type="text" name="unit" value="{{ old('unit', 'pcs') }}">
            </div>
            <div class="form-control">
                <label>Harga</label>
                <input type="number" name="price" min="0" step="100" value="{{ old('price') }}" required>
            </div>
            <div class="form-control">
                <label>Modal (Per Unit)</label>
                <input type="number" name="modal" min="0" step="100" value="{{ old('modal') }}" required>
            </div>
            <div class="form-control">
                <label>Stok</label>
                <input type="number" name="stock" min="0" value="{{ old('stock', 0) }}" required>
            </div>
            <div class="form-control">
                <label>Deskripsi</label>
                <textarea name="description">{{ old('description') }}</textarea>
            </div>
            <button class="btn btn-primary" type="submit">Simpan</button>
        </form>
    </div>
@endsection
