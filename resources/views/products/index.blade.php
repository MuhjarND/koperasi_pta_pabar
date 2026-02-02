@extends('layouts.app')

@section('title', 'Produk Koperasi')
@section('subtitle', 'Kelola daftar barang dan stok koperasi.')

@section('content')
    <div class="action-row" style="margin-bottom: 16px;">
        <button class="btn btn-primary" type="button" data-modal-open="product-modal">Tambah Produk</button>
        <button class="btn btn-ghost" type="button" data-modal-open="product-report-modal">Laporan</button>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'box'])</div>
                <h3>Daftar Produk</h3>
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Unit</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    @php
                        $modal = (float) ($product->modal ?? 0);
                        $stock = (int) ($product->stock ?? 0);
                        $sold = (int) ($product->sold_qty ?? 0);
                        $totalModal = $modal * $stock;
                        $hpp = $modal * $sold;
                        $price = (float) ($product->price ?? 0);
                        $revenue = $price * $sold;
                        $profit = $revenue - $hpp;
                    @endphp
                    <tr>
                        <td>
                            <details class="loan-drop">
                                <summary>
                                    <div class="loan-drop-title">
                                        <strong>{{ $product->name }}</strong>
                                        <span class="loan-drop-meta">Klik untuk detail</span>
                                    </div>
                                </summary>
                                <div class="loan-drop-body">
                                    <div class="loan-subcard">
                                        <ul class="ledger-list">
                                            <li>
                                                <span>Modal (per unit)</span>
                                                <span>Rp {{ number_format($modal, 2, ',', '.') }}</span>
                                            </li>
                                            <li>
                                                <span>Total Modal</span>
                                                <span>Rp {{ number_format($totalModal, 2, ',', '.') }}</span>
                                            </li>
                                            <li>
                                                <span>Terjual</span>
                                                <span>{{ $sold }} {{ $product->unit }}</span>
                                            </li>
                                            <li>
                                                <span>HPP</span>
                                                <span>Rp {{ number_format($hpp, 2, ',', '.') }}</span>
                                            </li>
                                            <li>
                                                <span>Harga Jual</span>
                                                <span>Rp {{ number_format($price, 2, ',', '.') }}</span>
                                            </li>
                                            <li>
                                                <span>Jumlah</span>
                                                <span>Rp {{ number_format($revenue, 2, ',', '.') }}</span>
                                            </li>
                                            <li>
                                                <span>Laba</span>
                                                <span>Rp {{ number_format($profit, 2, ',', '.') }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </details>
                        </td>
                        <td>Rp {{ number_format($price, 2, ',', '.') }}</td>
                        <td>{{ $product->stock }}</td>
                        <td>{{ $product->unit }}</td>
                        <td>
                            <button class="btn btn-ghost"
                                type="button"
                                data-edit-product
                                data-id="{{ $product->id }}"
                                data-name="{{ $product->name }}"
                                data-sku="{{ $product->sku }}"
                                data-unit="{{ $product->unit }}"
                                data-price="{{ $product->price }}"
                                data-modal="{{ $product->modal ?? 0 }}"
                                data-stock="{{ $product->stock }}"
                                data-description="{{ $product->description }}">
                                Edit
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Belum ada produk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <dialog class="modal" id="product-report-modal">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h3>Laporan Koperasi Mart</h3>
                    <p class="muted">Preview rekapitulasi penjualan dan stok.</p>
                </div>
                <button class="btn btn-ghost" type="button" data-modal-close-report>Keluar</button>
            </div>
            <iframe class="pdf-preview" src="{{ route('products.report') }}"></iframe>
        </div>
    </dialog>

    <dialog class="modal" id="product-modal">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h3>Tambah Produk</h3>
                    <p class="muted">Input data produk koperasi.</p>
                </div>
                <button class="btn btn-ghost" type="button" data-modal-close>Keluar</button>
            </div>
            <form method="post" action="{{ route('products.store') }}" class="form-grid">
                @csrf
                <div class="form-control">
                    <label>Nama Produk</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-control">
                    <label>SKU</label>
                    <input type="text" name="sku">
                </div>
                <div class="form-control">
                    <label>Unit</label>
                    <input type="text" name="unit" value="pcs">
                </div>
                <div class="form-control">
                    <label>Harga</label>
                    <input type="number" name="price" min="0" step="100" required>
                </div>
                <div class="form-control">
                    <label>Modal (Per Unit)</label>
                    <input type="number" name="modal" min="0" step="100" required>
                </div>
                <div class="form-control">
                    <label>Stok</label>
                    <input type="number" name="stock" min="0" value="0" required>
                </div>
                <div class="form-control">
                    <label>Deskripsi</label>
                    <textarea name="description"></textarea>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-ghost" type="button" data-modal-close>Batal</button>
                    <button class="btn btn-primary" type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog class="modal" id="product-edit-modal">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h3>Edit Produk</h3>
                    <p class="muted">Perbarui detail produk.</p>
                </div>
                <button class="btn btn-ghost" type="button" data-modal-close-edit>Keluar</button>
            </div>
            <form method="post" action="#" class="form-grid" data-edit-form>
                @csrf
                @method('put')
                <div class="form-control">
                    <label>Nama Produk</label>
                    <input type="text" name="name" data-edit-name required>
                </div>
                <div class="form-control">
                    <label>SKU</label>
                    <input type="text" name="sku" data-edit-sku>
                </div>
                <div class="form-control">
                    <label>Unit</label>
                    <input type="text" name="unit" data-edit-unit>
                </div>
                <div class="form-control">
                    <label>Harga</label>
                    <input type="number" name="price" min="0" step="100" data-edit-price required>
                </div>
                <div class="form-control">
                    <label>Modal (Per Unit)</label>
                    <input type="number" name="modal" min="0" step="100" data-edit-modal required>
                </div>
                <div class="form-control">
                    <label>Stok</label>
                    <input type="number" name="stock" min="0" data-edit-stock required>
                </div>
                <div class="form-control">
                    <label>Deskripsi</label>
                    <textarea name="description" data-edit-description></textarea>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-ghost" type="button" data-modal-close-edit>Batal</button>
                    <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        (function () {
            const createModal = document.getElementById('product-modal');
            const editModal = document.getElementById('product-edit-modal');
            const reportModal = document.getElementById('product-report-modal');
            const openButtons = document.querySelectorAll('[data-modal-open="product-modal"]');
            const closeButtons = createModal ? createModal.querySelectorAll('[data-modal-close]') : [];
            const closeEditButtons = editModal ? editModal.querySelectorAll('[data-modal-close-edit]') : [];
            const reportButtons = document.querySelectorAll('[data-modal-open="product-report-modal"]');
            const closeReportButtons = reportModal ? reportModal.querySelectorAll('[data-modal-close-report]') : [];
            const editButtons = document.querySelectorAll('[data-edit-product]');
            const editForm = editModal ? editModal.querySelector('[data-edit-form]') : null;

            const openModal = (modal) => {
                if (!modal) {
                    return;
                }
                if (typeof modal.showModal === 'function') {
                    modal.showModal();
                } else {
                    modal.setAttribute('open', 'open');
                }
            };

            const closeModal = (modal) => {
                if (!modal) {
                    return;
                }
                if (typeof modal.close === 'function') {
                    modal.close();
                } else {
                    modal.removeAttribute('open');
                }
            };

            openButtons.forEach((btn) => btn.addEventListener('click', () => openModal(createModal)));
            closeButtons.forEach((btn) => btn.addEventListener('click', () => closeModal(createModal)));
            closeEditButtons.forEach((btn) => btn.addEventListener('click', () => closeModal(editModal)));
            reportButtons.forEach((btn) => btn.addEventListener('click', () => openModal(reportModal)));
            closeReportButtons.forEach((btn) => btn.addEventListener('click', () => closeModal(reportModal)));

            if (createModal) {
                createModal.addEventListener('click', (event) => {
                    if (event.target === createModal) {
                        closeModal(createModal);
                    }
                });
            }

            if (editModal) {
                editModal.addEventListener('click', (event) => {
                    if (event.target === editModal) {
                        closeModal(editModal);
                    }
                });
            }

            if (reportModal) {
                reportModal.addEventListener('click', (event) => {
                    if (event.target === reportModal) {
                        closeModal(reportModal);
                    }
                });
            }

            editButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (!editForm) {
                        return;
                    }
                    const id = btn.getAttribute('data-id');
                    editForm.action = "{{ url('produk') }}/" + id;
                    editForm.querySelector('[data-edit-name]').value = btn.getAttribute('data-name') || '';
                    editForm.querySelector('[data-edit-sku]').value = btn.getAttribute('data-sku') || '';
                    editForm.querySelector('[data-edit-unit]').value = btn.getAttribute('data-unit') || 'pcs';
                    editForm.querySelector('[data-edit-price]').value = btn.getAttribute('data-price') || 0;
                    editForm.querySelector('[data-edit-modal]').value = btn.getAttribute('data-modal') || 0;
                    editForm.querySelector('[data-edit-stock]').value = btn.getAttribute('data-stock') || 0;
                    editForm.querySelector('[data-edit-description]').value = btn.getAttribute('data-description') || '';
                    openModal(editModal);
                });
            });
        })();
    </script>
@endsection

