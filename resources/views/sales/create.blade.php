@extends('layouts.app')

@section('title', 'Transaksi Penjualan')
@section('subtitle', 'Catat penjualan barang koperasi dan kurangi stok otomatis.')

@section('content')
    <form method="post" action="{{ route('sales.store') }}">
        @csrf
        <div class="grid-two">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <div class="card-icon">@include('partials.icon', ['name' => 'cart'])</div>
                        <h3>Detail Penjualan</h3>
                    </div>
                </div>
                <h3>Data Pembeli</h3>
                <div class="form-grid" style="margin-bottom: 16px;">
                    <div class="form-control">
                        <label>Nama Pembeli</label>
                        <select name="buyer_name">
                            <option value="">-- Pilih Pembeli --</option>
                            <option value="Pembeli Umum" @if(old('buyer_name') === 'Pembeli Umum') selected @endif>Pembeli Umum</option>
                            @foreach($members as $member)
                                <option value="{{ $member->name }}" @if(old('buyer_name') === $member->name) selected @endif>
                                    {{ $member->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h3>Item Penjualan</h3>
                <table class="table" id="items-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Qty</th>
                            <th>Harga</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                        <tr class="item-row">
                            <td>
                                <select name="items[0][product_id]" class="product-select" required>
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                            {{ $product->name }} (stok {{ $product->stock }} {{ $product->unit }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" name="items[0][qty]" class="qty-input" min="1" value="1" required>
                            </td>
                            <td class="price-cell">Rp 0</td>
                            <td class="subtotal-cell">Rp 0</td>
                            <td>
                                <button class="btn btn-ghost btn-remove" type="button">Hapus</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button class="btn btn-ghost" type="button" id="add-row">Tambah Item</button>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <div class="card-icon">@include('partials.icon', ['name' => 'chart'])</div>
                        <h3>Ringkasan</h3>
                    </div>
                </div>
                <table class="table">
                    <tr>
                        <td>Total</td>
                        <td id="sale-total">Rp 0</td>
                    </tr>
                </table>
                <div class="form-control">
                    <label>Catatan</label>
                    <textarea name="note" placeholder="Opsional">{{ old('note') }}</textarea>
                </div>
                <button class="btn btn-primary" type="submit">Simpan Transaksi</button>
            </div>
        </div>
    </form>

    <script>
        (function () {
            var body = document.getElementById('items-body');
            var addRowButton = document.getElementById('add-row');
            var totalCell = document.getElementById('sale-total');

            function formatRupiah(value) {
                return 'Rp ' + value.toLocaleString('id-ID');
            }

            function updateRow(row) {
                var select = row.querySelector('.product-select');
                var qtyInput = row.querySelector('.qty-input');
                var price = 0;

                if (select && select.selectedIndex >= 0) {
                    var option = select.options[select.selectedIndex];
                    price = parseFloat(option.getAttribute('data-price') || 0);
                }

                var qty = parseInt(qtyInput.value || 0, 10);
                var subtotal = price * qty;

                row.querySelector('.price-cell').textContent = formatRupiah(Math.round(price));
                row.querySelector('.subtotal-cell').textContent = formatRupiah(Math.round(subtotal));
            }

            function refreshTotal() {
                var total = 0;
                body.querySelectorAll('.item-row').forEach(function (row) {
                    var select = row.querySelector('.product-select');
                    var qtyInput = row.querySelector('.qty-input');
                    var price = 0;

                    if (select && select.selectedIndex >= 0) {
                        var option = select.options[select.selectedIndex];
                        price = parseFloat(option.getAttribute('data-price') || 0);
                    }

                    var qty = parseInt(qtyInput.value || 0, 10);
                    total += price * qty;
                });

                totalCell.textContent = formatRupiah(Math.round(total));
            }

            function reindexRows() {
                body.querySelectorAll('.item-row').forEach(function (row, index) {
                    row.querySelector('.product-select').name = 'items[' + index + '][product_id]';
                    row.querySelector('.qty-input').name = 'items[' + index + '][qty]';
                });
            }

            function bindRow(row) {
                row.querySelector('.product-select').addEventListener('change', function () {
                    updateRow(row);
                    refreshTotal();
                });
                row.querySelector('.qty-input').addEventListener('input', function () {
                    updateRow(row);
                    refreshTotal();
                });
                row.querySelector('.btn-remove').addEventListener('click', function () {
                    if (body.querySelectorAll('.item-row').length > 1) {
                        row.remove();
                        reindexRows();
                        refreshTotal();
                    }
                });
            }

            addRowButton.addEventListener('click', function () {
                var row = body.querySelector('.item-row').cloneNode(true);
                row.querySelector('.product-select').value = '';
                row.querySelector('.qty-input').value = 1;
                row.querySelector('.price-cell').textContent = 'Rp 0';
                row.querySelector('.subtotal-cell').textContent = 'Rp 0';
                body.appendChild(row);
                bindRow(row);
                reindexRows();
                refreshTotal();
            });

            body.querySelectorAll('.item-row').forEach(bindRow);
            refreshTotal();
        })();
    </script>
@endsection
