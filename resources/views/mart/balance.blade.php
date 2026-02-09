@extends('layouts.app')

@section('title', 'Saldo Koperasi Mart')
@section('subtitle', 'Saldo khusus koperasi mart yang terpisah dari saldo koperasi utama.')

@section('content')
    <div class="grid-two">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'wallet'])</div>
                    <h3>Saldo Koperasi Mart</h3>
                </div>
                @if($role === 'sekretaris')
                    <div class="action-row">
                        <button class="btn btn-primary" type="button" data-modal-open="mart-cash-in">Tambah Pemasukan</button>
                        <button class="btn btn-ghost" type="button" data-modal-open="mart-cash-out">Tambah Pengeluaran</button>
                    </div>
                @endif
            </div>
            <div class="stat">
                <div class="stat-icon">@include('partials.icon', ['name' => 'wallet'])</div>
                <div>
                    <div>Saldo Terkini</div>
                    <div class="value">Rp {{ number_format($balance ?? 0, 2, ',', '.') }}</div>
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
                    <div class="card-icon">@include('partials.icon', ['name' => 'clipboard'])</div>
                    <h3>Ringkasan Transaksi</h3>
                </div>
            </div>
            <table class="table table-compact">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Uraian</th>
                        <th>Nominal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries->take(6) as $entry)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($entry->entry_date)->format('d/m/Y') }}</td>
                            <td>{{ $entry->description }}</td>
                            <td>
                                {{ $entry->direction === 'in' ? 'Rp ' : '- Rp ' }}
                                {{ number_format($entry->amount ?? 0, 2, ',', '.') }}
                            </td>
                            <td>{{ strtoupper($entry->status ?? 'APPROVED') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">Belum ada transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(in_array($role, ['sekretaris', 'superadmin']))
        <div class="card" style="margin-top: 16px;">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'check'])</div>
                    <h3>Verifikasi Koperasi Mart</h3>
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
                                    <form method="post" action="{{ route('mart.balance.verify', $entry->id) }}">
                                        @csrf
                                        <button class="btn btn-primary" type="submit">Verifikasi</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="muted">Tidak ada transaksi koperasi mart yang menunggu verifikasi.</div>
            @endif
        </div>
    @endif

    <div class="card" style="margin-top: 16px;">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'history'])</div>
                <h3>Arus Kas Koperasi Mart</h3>
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
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Uraian</th>
                    <th>Pemasukan</th>
                    <th>Pengeluaran</th>
                    <th>Bukti</th>
                    <th>Saldo</th>
                    <th>Status</th>
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
                        <td>{{ $row['description'] }}</td>
                        <td>
                            @if(($row['receipts_total'] ?? 0) > 0)
                                Rp {{ number_format($row['receipts_total'] ?? 0, 2, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if(($row['expenses_total'] ?? 0) > 0)
                                Rp {{ number_format($row['expenses_total'] ?? 0, 2, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if(!empty($row['evidence_path']))
                                @php
                                    $entryExt = strtolower(pathinfo($row['evidence_path'], PATHINFO_EXTENSION));
                                    $entryType = $entryExt === 'pdf' ? 'pdf' : 'image';
                                @endphp
                                <button
                                    type="button"
                                    class="btn btn-ghost"
                                    data-evidence-open
                                    data-evidence-src="{{ asset($row['evidence_path']) }}"
                                    data-evidence-type="{{ $entryType }}"
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
                            Rp {{ number_format($row['balance'] ?? 0, 2, ',', '.') }}
                        </td>
                        <td>{{ strtoupper($row['status'] ?? 'APPROVED') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">Belum ada transaksi koperasi mart.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($role === 'sekretaris')
        <dialog class="modal" id="mart-cash-in">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Tambah Pemasukan</h3>
                        <p class="muted">Catat pemasukan koperasi mart.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close>Keluar</button>
                </div>
                <form method="post" action="{{ route('mart.balance.store') }}" class="form-grid" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="direction" value="in">
                    <div class="form-control">
                        <label>Tanggal</label>
                        <input type="date" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="form-control">
                        <label>Uraian</label>
                        <input type="text" name="description" value="{{ old('description') }}" required>
                    </div>
                    <div class="form-control">
                        <label>Jumlah</label>
                        <input type="number" name="amount" min="0" step="1000" value="{{ old('amount') }}" required>
                    </div>
                    <div class="form-control">
                        <label>Eviden (jpg/png/pdf)</label>
                        <input type="file" name="evidence" accept=".jpg,.jpeg,.png,.pdf" required>
                    </div>
                    <div class="modal-actions">
                        <button class="btn btn-ghost" type="button" data-modal-close>Batal</button>
                        <button class="btn btn-primary" type="submit">Simpan</button>
                    </div>
                </form>
            </div>
        </dialog>

        <dialog class="modal" id="mart-cash-out">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Tambah Pengeluaran</h3>
                        <p class="muted">Catat pengeluaran koperasi mart.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close>Keluar</button>
                </div>
                <form method="post" action="{{ route('mart.balance.store') }}" class="form-grid" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="direction" value="out">
                    <div class="form-control">
                        <label>Tanggal</label>
                        <input type="date" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="form-control">
                        <label>Uraian</label>
                        <input type="text" name="description" value="{{ old('description') }}" required>
                    </div>
                    <div class="form-control">
                        <label>Jumlah</label>
                        <input type="number" name="amount" min="0" step="1000" value="{{ old('amount') }}" required>
                    </div>
                    <div class="form-control">
                        <label>Eviden (jpg/png/pdf)</label>
                        <input type="file" name="evidence" accept=".jpg,.jpeg,.png,.pdf" required>
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
                    'mart-cash-in': document.getElementById('mart-cash-in'),
                    'mart-cash-out': document.getElementById('mart-cash-out')
                };
                const openButtons = document.querySelectorAll('[data-modal-open]');
                const closeButtons = document.querySelectorAll('[data-modal-close]');

                const openModal = (modal) => {
                    if (!modal) return;
                    if (typeof modal.showModal === 'function') {
                        modal.showModal();
                    } else {
                        modal.setAttribute('open', 'open');
                    }
                };

                const closeModal = (modal) => {
                    if (!modal) return;
                    if (typeof modal.close === 'function') {
                        modal.close();
                    } else {
                        modal.removeAttribute('open');
                    }
                };

                openButtons.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const target = btn.getAttribute('data-modal-open');
                        if (modals[target]) {
                            openModal(modals[target]);
                        }
                    });
                });

                closeButtons.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        Object.values(modals).forEach((modal) => closeModal(modal));
                    });
                });

                Object.values(modals).forEach((modal) => {
                    if (!modal) return;
                    modal.addEventListener('click', (event) => {
                        if (event.target === modal) {
                            closeModal(modal);
                        }
                    });
                });
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
                if (!modal) return;
                if (typeof modal.showModal === 'function') {
                    modal.showModal();
                } else {
                    modal.setAttribute('open', 'open');
                }
            };

            const closeModal = () => {
                if (!modal) return;
                if (typeof modal.close === 'function') {
                    modal.close();
                } else {
                    modal.removeAttribute('open');
                }
                if (pdfFrame) pdfFrame.removeAttribute('src');
                if (imageEl) imageEl.removeAttribute('src');
            };

            openButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const src = btn.getAttribute('data-evidence-src');
                    const type = btn.getAttribute('data-evidence-type');
                    const label = btn.getAttribute('data-evidence-title') || 'Bukti Transaksi';

                    if (title) title.textContent = label;

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
