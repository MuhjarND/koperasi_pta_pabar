@extends('layouts.app')

@section('title', 'Saldo Koperasi')
@section('subtitle', 'Saldo koperasi dihitung dari pemotongan simpanan dan angsuran anggota.')

@section('content')
    <div class="grid-two">
        <div class="card balance-card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'wallet'])</div>
                    <h3>Saldo Terkini</h3>
                </div>
                @if($role === 'bendahara')
                    <div class="action-row">
                        <button class="btn btn-primary" type="button" data-modal-open="cash-in-modal">Tambah Pemasukan</button>
                        <button class="btn btn-ghost" type="button" data-modal-open="cash-out-modal">Tambah Pengeluaran</button>
                    </div>
                @endif
            </div>
            <div class="stat">
                <div class="stat-icon">
                    @include('partials.icon', ['name' => 'wallet'])
                </div>
                <div>
                    <div>Saldo Koperasi</div>
                    <div class="value">Rp {{ number_format($amount ?? 0, 2, ',', '.') }}</div>
                </div>
            </div>
            <p class="muted">
                Terakhir diperbarui:
                {{ $lastTransaction && $lastTransaction->created_at ? $lastTransaction->created_at : 'Belum ada transaksi' }}
            </p>
            <p class="muted">
                Oleh: {{ $lastTransaction && $lastTransaction->created_by_name ? $lastTransaction->created_by_name : '-' }}
            </p>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'coins'])</div>
                    <h3>Rincian Simpanan</h3>
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Jenis</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($types as $key => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>Rp {{ number_format($typeTotals[$key] ?? 0, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if(in_array($role, ['sekretaris', 'superadmin']))
        <div class="card" style="margin-top: 16px;">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'check'])</div>
                    <h3>Verifikasi Pemasukan & Pengeluaran</h3>
                </div>
            </div>
            @if($pendingEntries && count($pendingEntries))
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Uraian</th>
                            <th>Jumlah</th>
                            <th>Kategori</th>
                            <th>Bukti</th>
                            <th>Input Oleh</th>
                            <th>Catatan Edit</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingEntries as $entry)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($entry->entry_date)->format('d/m/Y') }}</td>
                                <td>{{ $entry->direction === 'in' ? 'Pemasukan' : 'Pengeluaran' }}</td>
                                <td>{{ $entry->description }}</td>
                                <td>Rp {{ number_format($entry->amount ?? 0, 2, ',', '.') }}</td>
                                <td>{{ $entry->category ? strtoupper(str_replace('_', ' ', $entry->category)) : '-' }}</td>
                                <td>
                                    @if(!empty($entry->evidence_path))
                                        @php
                                            $entryExt = strtolower(pathinfo($entry->evidence_path, PATHINFO_EXTENSION));
                                            $entryType = $entryExt === 'pdf' ? 'pdf' : 'image';
                                        @endphp
                                        <button
                                            type="button"
                                            class="btn btn-ghost"
                                            data-evidence-open
                                            data-evidence-src="{{ asset($entry->evidence_path) }}"
                                            data-evidence-type="{{ $entryType }}"
                                            data-evidence-title="Bukti {{ $entry->direction === 'in' ? 'Pemasukan' : 'Pengeluaran' }}"
                                        >
                                            Lihat
                                        </button>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $entry->created_by_name ?? '-' }}</td>
                                <td>
                                    @if(!empty($entry->edit_note))
                                        <div>{{ $entry->edit_note }}</div>
                                        <small class="muted">
                                            @if(!empty($entry->edited_by_name))
                                                Oleh {{ $entry->edited_by_name }}
                                            @endif
                                            @if(!empty($entry->edited_at))
                                                {{ \Carbon\Carbon::parse($entry->edited_at)->format('d/m/Y H:i') }}
                                            @endif
                                        </small>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <form method="post" action="{{ route('saldo.verify', $entry->id) }}">
                                        @csrf
                                        <button class="btn btn-primary" type="submit">Verifikasi</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="muted">Tidak ada pemasukan/pengeluaran yang menunggu verifikasi.</div>
            @endif
        </div>
    @endif

    <div class="card" style="margin-top: 16px;">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'clipboard'])</div>
                <h3>Arus Kas Bulanan</h3>
            </div>
        </div>
        <form method="get" class="action-row" style="margin-bottom: 16px;">
            <select name="month">
                @foreach($monthNames as $number => $label)
                    <option value="{{ $number }}" @if($month == $number) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
            <select name="year">
                @foreach($availableYears as $availableYear)
                    <option value="{{ $availableYear }}" @if($year == $availableYear) selected @endif>{{ $availableYear }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Terapkan</button>
            <a class="btn btn-ghost" href="{{ route('saldo.export', ['month' => $month, 'year' => $year]) }}">Export Excel</a>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Uraian</th>
                    <th>Penerimaan</th>
                    <th>Pengeluaran</th>
                    <th>Bukti</th>
                    <th>Saldo</th>
                    @if($role === 'bendahara')
                        <th>Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($ledgerRows as $row)
                    @php
                        $delta = ($row['receipts_total'] ?? 0) - ($row['expenses_total'] ?? 0);
                        $directionClass = $delta > 0 ? 'saldo-up' : ($delta < 0 ? 'saldo-down' : 'saldo-flat');
                        $directionIcon = $delta > 0 ? '&uarr;' : ($delta < 0 ? '&darr;' : '&minus;');
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                        <td>
                            @if(!empty($row['description_items']) && count($row['description_items']) > 1)
                                <details class="ledger-drop">
                                    <summary>
                                        <span>{{ $row['description'] }}</span>
                                        <span class="ledger-eye" aria-label="Detail">@include('partials.icon', ['name' => 'eye'])</span>
                                    </summary>
                                    <div class="ledger-body">
                                        <ul class="ledger-list">
                                            @foreach($row['description_items'] as $item)
                                                <li>{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </details>
                            @else
                                {{ $row['description'] }}
                            @endif
                        </td>
                        <td>
                            @if($row['receipts'])
                                <details class="ledger-drop">
                                    <summary>
                                        <span>Rp {{ number_format($row['receipts_total'], 2, ',', '.') }}</span>
                                        <span class="ledger-eye" aria-label="Detail">@include('partials.icon', ['name' => 'eye'])</span>
                                    </summary>
                                    <div class="ledger-body">
                                        @php
                                            $receiptDetails = [
                                                ['label' => 'Simpanan Pokok', 'amount' => (float) ($row['receipts']['pokok'] ?? 0)],
                                                ['label' => 'Simpanan Wajib', 'amount' => (float) ($row['receipts']['wajib'] ?? 0)],
                                                ['label' => 'Simpanan Sukarela', 'amount' => (float) ($row['receipts']['sukarela'] ?? 0)],
                                                ['label' => 'Angsuran Pokok', 'amount' => (float) ($row['receipts']['principal'] ?? 0)],
                                                ['label' => 'Angsuran Jasa', 'amount' => (float) ($row['receipts']['fee'] ?? 0)],
                                                ['label' => 'Lain-lain', 'amount' => (float) ($row['receipts']['other'] ?? 0)],
                                            ];
                                            $shownReceiptDetails = array_values(array_filter($receiptDetails, function ($item) {
                                                return abs((float) $item['amount']) > 0.000001;
                                            }));
                                        @endphp
                                        @if(count($shownReceiptDetails))
                                            @foreach($shownReceiptDetails as $detail)
                                                <div class="ledger-item">
                                                    <span>{{ $detail['label'] }}</span>
                                                    <span>Rp {{ number_format($detail['amount'], 2, ',', '.') }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="muted">Tidak ada rincian penerimaan.</div>
                                        @endif
                                        @if(!empty($row['potongan_totals']))
                                            <div class="ledger-subtitle">Ringkasan Potongan Gaji</div>
                                            <div class="ledger-item">
                                                <span>Total Simpanan (Wajib + Sukarela)</span>
                                                <span>Rp {{ number_format($row['potongan_totals']['savings'], 2, ',', '.') }}</span>
                                            </div>
                                            <div class="ledger-item">
                                                <span>Total Angsuran</span>
                                                <span>Rp {{ number_format($row['potongan_totals']['installment'], 2, ',', '.') }}</span>
                                            </div>
                                        @endif
                                        @if(!empty($row['receipts']['other_items']))
                                            <div class="ledger-subtitle">Rincian Lain-lain</div>
                                            <ul class="ledger-list">
                                                @foreach($row['receipts']['other_items'] as $item)
                                                    <li>
                                                        <span>{{ $item['label'] }}</span>
                                                        <span>Rp {{ number_format($item['amount'], 2, ',', '.') }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </details>
                            @else
                                Rp 0
                            @endif
                        </td>
                        <td>
                            @if($row['expenses'])
                                <details class="ledger-drop">
                                    <summary>
                                        <span>Rp {{ number_format($row['expenses_total'], 2, ',', '.') }}</span>
                                        <span class="ledger-eye" aria-label="Detail">@include('partials.icon', ['name' => 'eye'])</span>
                                    </summary>
                                    <div class="ledger-body">
                                        @if(!empty($row['expenses']['items']))
                                            <ul class="ledger-list">
                                                @foreach($row['expenses']['items'] as $item)
                                                    <li>
                                                        <span>{{ $item['label'] }}</span>
                                                        <span>Rp {{ number_format($item['amount'], 2, ',', '.') }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="muted">Belum ada pengeluaran.</div>
                                        @endif
                                    </div>
                                </details>
                            @else
                                Rp 0
                            @endif
                        </td>
                        <td>
                            @if(!empty($row['evidence_path']))
                                @php
                                    $evidenceExt = strtolower(pathinfo($row['evidence_path'], PATHINFO_EXTENSION));
                                    $evidenceType = $evidenceExt === 'pdf' ? 'pdf' : 'image';
                                @endphp
                                <button
                                    type="button"
                                    class="btn btn-ghost"
                                    data-evidence-open
                                    data-evidence-src="{{ asset($row['evidence_path']) }}"
                                    data-evidence-type="{{ $evidenceType }}"
                                    data-evidence-title="Bukti {{ $row['description'] }}"
                                >
                                    Lihat
                                </button>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <span class="saldo-indicator {{ $directionClass }}">{!! $directionIcon !!}</span>
                            Rp {{ number_format($row['balance'], 2, ',', '.') }}
                        </td>
                        @if($role === 'bendahara')
                            <td>
                                @if(($row['source_type'] ?? '') === 'cash' && !empty($row['can_edit']))
                                    <button
                                        type="button"
                                        class="btn btn-ghost"
                                        data-modal-open="cash-edit-modal"
                                        data-cash-id="{{ $row['source_id'] ?? '' }}"
                                        data-cash-date="{{ $row['date'] ?? '' }}"
                                        data-cash-direction="{{ $row['entry_direction'] ?? '' }}"
                                        data-cash-category="{{ $row['entry_category'] ?? '' }}"
                                        data-cash-description="{{ $row['description'] ?? '' }}"
                                        data-cash-amount="{{ $row['receipts_total'] > 0 ? $row['receipts_total'] : $row['expenses_total'] }}"
                                        data-cash-note="{{ $row['entry_edit_note'] ?? '' }}"
                                    >
                                        @include('partials.icon', ['name' => 'edit']) Edit
                                    </button>
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $role === 'bendahara' ? 8 : 7 }}">Belum ada transaksi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($role === 'bendahara')
        <dialog class="modal" id="cash-in-modal">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Tambah Pemasukan</h3>
                        <p class="muted">Masukkan pemasukan lainnya di luar simpanan.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close>Keluar</button>
                </div>
                <form method="post" action="{{ route('saldo.update') }}" class="form-grid" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="direction" value="in">
                    <div class="form-control">
                        <label>Tanggal</label>
                        <input type="date" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="form-control">
                        <label>Uraian Pemasukan</label>
                        <input type="text" name="description" value="{{ old('description') }}" required>
                    </div>
                    <div class="form-control">
                        <label>Jumlah</label>
                        <input type="number" name="amount" min="0" step="1000" value="{{ old('amount') }}" required>
                    </div>
                    <div class="form-control">
                        <label>Eviden Pemasukan (jpg/png/pdf)</label>
                        <input type="file" name="evidence" accept=".jpg,.jpeg,.png,.pdf" required>
                    </div>
                    <div class="modal-actions">
                        <button class="btn btn-ghost" type="button" data-modal-close>Batal</button>
                        <button class="btn btn-primary" type="submit">Simpan</button>
                    </div>
                </form>
            </div>
        </dialog>

        <dialog class="modal" id="cash-out-modal">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Tambah Pengeluaran</h3>
                        <p class="muted">Catat pengeluaran koperasi.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close>Keluar</button>
                </div>
                <form method="post" action="{{ route('saldo.update') }}" class="form-grid" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="direction" value="out">
                    <div class="form-control">
                        <label>Tanggal</label>
                        <input type="date" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="form-control">
                        <label>Kategori Pengeluaran</label>
                        <select name="category" id="expense-category" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="rat" @if(old('category') === 'rat') selected @endif>Biaya Rapat Anggota Tahunan</option>
                            <option value="adm" @if(old('category') === 'adm') selected @endif>Biaya ADM</option>
                            <option value="adm_transfer" @if(old('category') === 'adm_transfer') selected @endif>Biaya ADM Transfer</option>
                            <option value="atk" @if(old('category') === 'atk') selected @endif>Biaya ATK</option>
                            <option value="lain-lain" @if(old('category') === 'lain-lain') selected @endif>Biaya Lain-lain</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label>Keterangan</label>
                        <input type="text" name="description" value="{{ old('description') }}" required>
                    </div>
                    <div class="form-control">
                        <label>Eviden Pengeluaran (jpg/png/pdf)</label>
                        <input type="file" name="evidence" accept=".jpg,.jpeg,.png,.pdf" required>
                    </div>
                    <div class="form-control">
                        <label>Jumlah</label>
                        <input type="number" name="amount" min="0" step="1000" value="{{ old('amount') }}" required>
                    </div>
                    <div class="modal-actions">
                        <button class="btn btn-ghost" type="button" data-modal-close>Batal</button>
                        <button class="btn btn-primary" type="submit">Simpan</button>
                    </div>
                </form>
            </div>
        </dialog>

        <dialog class="modal" id="cash-edit-modal">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Edit Transaksi Arus Kas</h3>
                        <p class="muted">Perubahan akan diverifikasi ulang oleh sekretaris.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close>Keluar</button>
                </div>
                <form method="post" action="" class="form-grid" enctype="multipart/form-data" id="cash-edit-form">
                    @csrf
                    <input type="hidden" name="_cash_edit" value="1">
                    <input type="hidden" name="cash_entry_id" id="cash-edit-id" value="">
                    <div class="form-control">
                        <label>Jenis</label>
                        <input type="text" id="cash-edit-direction-text" value="-" disabled>
                    </div>
                    <div class="form-control">
                        <label>Tanggal</label>
                        <input type="date" id="cash-edit-date" name="entry_date" required>
                    </div>
                    <div class="form-control" id="cash-edit-category-wrap">
                        <label>Kategori Pengeluaran</label>
                        <select id="cash-edit-category" name="category">
                            <option value="">-- Pilih Kategori --</option>
                            <option value="rat">Biaya Rapat Anggota Tahunan</option>
                            <option value="adm">Biaya ADM</option>
                            <option value="adm_transfer">Biaya ADM Transfer</option>
                            <option value="atk">Biaya ATK</option>
                            <option value="lain-lain">Biaya Lain-lain</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label>Uraian</label>
                        <input type="text" id="cash-edit-description" name="description" required>
                    </div>
                    <div class="form-control">
                        <label>Jumlah</label>
                        <input type="number" id="cash-edit-amount" name="amount" min="0" step="1000" required>
                    </div>
                    <div class="form-control">
                        <label>Catatan untuk Sekretaris</label>
                        <textarea id="cash-edit-note" name="edit_note" placeholder="Tuliskan alasan/perubahan yang dilakukan" required></textarea>
                    </div>
                    <div class="form-control">
                        <label>Ganti Eviden (opsional, jpg/png/pdf)</label>
                        <input type="file" name="evidence" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                    <div class="modal-actions">
                        <button class="btn btn-ghost" type="button" data-modal-close>Batal</button>
                        <button class="btn btn-primary" type="submit">Kirim Perubahan</button>
                    </div>
                </form>
            </div>
        </dialog>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modals = {
                    'cash-in-modal': document.getElementById('cash-in-modal'),
                    'cash-out-modal': document.getElementById('cash-out-modal'),
                    'cash-edit-modal': document.getElementById('cash-edit-modal')
                };
                const openButtons = document.querySelectorAll('[data-modal-open]');
                const closeButtons = document.querySelectorAll('[data-modal-close]');
                const shouldOpen = @json($errors->any());
                const direction = @json(old('direction'));
                const isEditError = @json(old('_cash_edit') ? true : false);
                const editForm = document.getElementById('cash-edit-form');
                const editDirectionText = document.getElementById('cash-edit-direction-text');
                const editDateInput = document.getElementById('cash-edit-date');
                const editIdInput = document.getElementById('cash-edit-id');
                const editCategoryWrap = document.getElementById('cash-edit-category-wrap');
                const editCategoryInput = document.getElementById('cash-edit-category');
                const editDescriptionInput = document.getElementById('cash-edit-description');
                const editAmountInput = document.getElementById('cash-edit-amount');
                const editNoteInput = document.getElementById('cash-edit-note');

                const setEditMode = (mode) => {
                    const outMode = mode === 'out';
                    if (editDirectionText) {
                        editDirectionText.value = outMode ? 'Pengeluaran' : 'Pemasukan';
                    }
                    if (editCategoryWrap) {
                        editCategoryWrap.style.display = outMode ? 'grid' : 'none';
                    }
                    if (editCategoryInput) {
                        editCategoryInput.required = outMode;
                    }
                };

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

                openButtons.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const target = btn.getAttribute('data-modal-open');
                        if (target === 'cash-edit-modal' && editForm) {
                            const cashId = btn.getAttribute('data-cash-id');
                            const cashDirection = btn.getAttribute('data-cash-direction') || 'in';
                            editForm.setAttribute('action', '{{ route('saldo.edit', ['id' => '__ID__']) }}'.replace('__ID__', cashId));
                            if (editIdInput) {
                                editIdInput.value = cashId || '';
                            }
                            setEditMode(cashDirection);
                            if (editDateInput) {
                                editDateInput.value = btn.getAttribute('data-cash-date') || '';
                            }
                            if (editCategoryInput) {
                                editCategoryInput.value = btn.getAttribute('data-cash-category') || '';
                            }
                            if (editDescriptionInput) {
                                editDescriptionInput.value = btn.getAttribute('data-cash-description') || '';
                            }
                            if (editAmountInput) {
                                editAmountInput.value = btn.getAttribute('data-cash-amount') || '';
                            }
                            if (editNoteInput) {
                                editNoteInput.value = btn.getAttribute('data-cash-note') || '';
                            }
                        }
                        openModal(modals[target]);
                    });
                });

                closeButtons.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        Object.values(modals).forEach((modal) => closeModal(modal));
                    });
                });

                Object.values(modals).forEach((modal) => {
                    if (modal) {
                        modal.addEventListener('click', (event) => {
                            if (event.target === modal) {
                                closeModal(modal);
                            }
                        });
                    }
                });

                if (shouldOpen) {
                    let modalId = direction === 'out' ? 'cash-out-modal' : 'cash-in-modal';
                    if (isEditError) {
                        modalId = 'cash-edit-modal';
                        if (editForm) {
                            const failedId = @json(old('cash_entry_id'));
                            if (failedId) {
                                editForm.setAttribute('action', '{{ route('saldo.edit', ['id' => '__ID__']) }}'.replace('__ID__', failedId));
                                if (editIdInput) {
                                    editIdInput.value = failedId;
                                }
                            }
                        }
                        setEditMode(@json(old('category') ? 'out' : 'in'));
                        if (editDateInput) {
                            editDateInput.value = @json(old('entry_date'));
                        }
                        if (editCategoryInput) {
                            editCategoryInput.value = @json(old('category'));
                        }
                        if (editDescriptionInput) {
                            editDescriptionInput.value = @json(old('description'));
                        }
                        if (editAmountInput) {
                            editAmountInput.value = @json(old('amount'));
                        }
                        if (editNoteInput) {
                            editNoteInput.value = @json(old('edit_note'));
                        }
                    }
                    openModal(modals[modalId]);
                }

            });
        </script>
    @endif

    <dialog class="modal" id="evidence-modal">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h3 id="evidence-title">Bukti Transaksi</h3>
                    <p class="muted">Pratinjau eviden transaksi.</p>
                </div>
                <button class="btn btn-ghost" type="button" data-evidence-close>Keluar</button>
            </div>
            <div class="modal-body">
                <iframe id="evidence-pdf" class="pdf-preview" title="Bukti PDF" style="display: none;"></iframe>
                <img id="evidence-image" alt="Bukti transaksi" style="display: none; width: 100%; border-radius: 16px;">
            </div>
        </div>
    </dialog>

    <script>
        (function () {
            const modal = document.getElementById('evidence-modal');
            const title = document.getElementById('evidence-title');
            const pdfFrame = document.getElementById('evidence-pdf');
            const imageEl = document.getElementById('evidence-image');
            const openButtons = document.querySelectorAll('[data-evidence-open]');
            const closeButton = document.querySelector('[data-evidence-close]');

            const openModal = () => {
                if (!modal) {
                    return;
                }
                if (typeof modal.showModal === 'function') {
                    modal.showModal();
                } else {
                    modal.setAttribute('open', 'open');
                }
            };

            const closeModal = () => {
                if (!modal) {
                    return;
                }
                if (typeof modal.close === 'function') {
                    modal.close();
                } else {
                    modal.removeAttribute('open');
                }
                if (pdfFrame) {
                    pdfFrame.removeAttribute('src');
                }
                if (imageEl) {
                    imageEl.removeAttribute('src');
                }
            };

            openButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const src = btn.getAttribute('data-evidence-src');
                    const type = btn.getAttribute('data-evidence-type');
                    const label = btn.getAttribute('data-evidence-title') || 'Bukti Transaksi';

                    if (title) {
                        title.textContent = label;
                    }

                    if (type === 'pdf') {
                        if (pdfFrame) {
                            pdfFrame.style.display = 'block';
                            pdfFrame.src = src;
                        }
                        if (imageEl) {
                            imageEl.style.display = 'none';
                            imageEl.removeAttribute('src');
                        }
                    } else {
                        if (imageEl) {
                            imageEl.style.display = 'block';
                            imageEl.src = src;
                        }
                        if (pdfFrame) {
                            pdfFrame.style.display = 'none';
                            pdfFrame.removeAttribute('src');
                        }
                    }

                    openModal();
                });
            });

            if (closeButton) {
                closeButton.addEventListener('click', closeModal);
            }

            if (modal) {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal();
                    }
                });
            }
        })();
    </script>
@endsection

