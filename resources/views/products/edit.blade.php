@extends('layouts.app')

@section('title', 'Edit Produk')
@section('subtitle', 'Perbarui detail barang koperasi.')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'box'])</div>
                <h3>Detail Produk</h3>
            </div>
        </div>
        <form method="post" action="{{ route('products.update', $product->id) }}" class="form-grid">
            @csrf
            <div class="form-control">
                <label>Nama Produk</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" required>
            </div>
            <div class="form-control">
                <label>SKU</label>
                <input type="text" name="sku" value="{{ old('sku', $product->sku) }}">
            </div>
            <div class="form-control">
                <label>Unit</label>
                <input type="text" name="unit" value="{{ old('unit', $product->unit) }}">
            </div>
            <div class="form-control">
                <label>Harga</label>
                <input type="number" name="price" min="0" step="100" value="{{ old('price', $product->price) }}" required>
            </div>
            <div class="form-control">
                <label>Modal (Per Unit)</label>
                <input type="number" name="modal" min="0" step="100" value="{{ old('modal', $product->modal ?? 0) }}" required>
            </div>
            <div class="form-control">
                <label>Stok</label>
                <input type="number" name="stock" min="0" value="{{ old('stock', $product->stock) }}" required>
            </div>
            <div class="form-control">
                <label>Deskripsi</label>
                <textarea name="description">{{ old('description', $product->description) }}</textarea>
            </div>
            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
        </form>
    </div>
@endsection
