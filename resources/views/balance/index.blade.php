@extends('layouts.app')

@section('title', 'Saldo Koperasi')
@section('subtitle', 'Saldo koperasi dihitung dari pemotongan simpanan dan angsuran anggota.')

@section('content')
    <div class="grid-two">
        <div class="card">
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
                                        <div class="ledger-item">
                                            <span>Simpanan Pokok</span>
                                            <span>Rp {{ number_format($row['receipts']['pokok'], 2, ',', '.') }}</span>
                                        </div>
                                        <div class="ledger-item">
                                            <span>Simpanan Wajib</span>
                                            <span>Rp {{ number_format($row['receipts']['wajib'], 2, ',', '.') }}</span>
                                        </div>
                                        <div class="ledger-item">
                                            <span>Simpanan Sukarela</span>
                                            <span>Rp {{ number_format($row['receipts']['sukarela'], 2, ',', '.') }}</span>
                                        </div>
                                        <div class="ledger-item">
                                            <span>Angsuran Pokok</span>
                                            <span>Rp {{ number_format($row['receipts']['principal'], 2, ',', '.') }}</span>
                                        </div>
                                        <div class="ledger-item">
                                            <span>Angsuran Jasa</span>
                                            <span>Rp {{ number_format($row['receipts']['fee'], 2, ',', '.') }}</span>
                                        </div>
                                        <div class="ledger-item">
                                            <span>Lain-lain</span>
                                            <span>Rp {{ number_format($row['receipts']['other'], 2, ',', '.') }}</span>
                                        </div>
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
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Belum ada transaksi.</td>
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

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modals = {
                    'cash-in-modal': document.getElementById('cash-in-modal'),
                    'cash-out-modal': document.getElementById('cash-out-modal')
                };
                const openButtons = document.querySelectorAll('[data-modal-open]');
                const closeButtons = document.querySelectorAll('[data-modal-close]');
                const shouldOpen = @json($errors->any());
                const direction = @json(old('direction'));

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
                    const modalId = direction === 'out' ? 'cash-out-modal' : 'cash-in-modal';
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

