@extends('layouts.app')

@section('title', 'Simpanan Anggota')
@section('subtitle', 'Rekap simpanan per anggota dengan detail bulanan.')

@section('content')
    @if($role === 'anggota')
        @php
            $memberSummary = $memberSummaries[0] ?? null;
            $totalMonths = $memberSummary ? count($memberSummary['months']) : 0;
        @endphp
        <div class="summary-grid" style="margin-bottom: 16px;">
            <div class="summary-item accent">
                <div class="label">Total Simpanan</div>
                <div class="value">Rp {{ number_format($memberSummary['total_amount'] ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Simpanan Pokok</div>
                <div class="value">Rp {{ number_format($memberTypeTotals['pokok'] ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Simpanan Wajib</div>
                <div class="value">Rp {{ number_format($memberTypeTotals['wajib'] ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Simpanan Sukarela</div>
                <div class="value">Rp {{ number_format($memberTypeTotals['sukarela'] ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="summary-item warning">
                <div class="label">Bulan Tercatat</div>
                <div class="value">{{ $totalMonths }} bulan</div>
            </div>
        </div>
    @elseif(!in_array($role, ['bendahara_kantor']))
        <div class="summary-grid" style="margin-bottom: 16px;">
            <div class="summary-item accent">
                <div class="label">Total Simpanan</div>
                <div class="value">Rp {{ number_format($summaryTotals['total'] ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Simpanan Pokok</div>
                <div class="value">Rp {{ number_format($summaryTotals['types']['pokok'] ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Simpanan Wajib</div>
                <div class="value">Rp {{ number_format($summaryTotals['types']['wajib'] ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Simpanan Sukarela</div>
                <div class="value">Rp {{ number_format($summaryTotals['types']['sukarela'] ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="summary-item warning">
                <div class="label">Jumlah Anggota</div>
                <div class="value">{{ $summaryTotals['members'] ?? 0 }} anggota</div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'coins'])</div>
                <h3>Rekap Simpanan</h3>
            </div>
            @if($role === 'bendahara')
                <div class="action-row">
                    <button class="btn btn-primary" type="button" data-modal-open="savings-modal">Input Simpanan</button>
                    <button class="btn btn-ghost" type="button" data-modal-open="savings-post-modal" @if(($pendingSavings['count'] ?? 0) < 1) disabled @endif>
                        Masukkan ke Arus Kas
                    </button>
                    <button class="btn btn-ghost" type="button" data-modal-open="savings-rekap-modal">Preview Rekap PDF</button>
                </div>
            @elseif(!in_array($role, ['bendahara_kantor', 'anggota']))
                <div class="action-row">
                    <button class="btn btn-ghost" type="button" data-modal-open="savings-rekap-modal">Preview Rekap PDF</button>
                </div>
            @endif
        </div>
        @if($role === 'bendahara')
            <p class="muted">
                Menunggu masuk arus kas:
                {{ $pendingSavings['count'] ?? 0 }} transaksi
                (Rp {{ number_format($pendingSavings['total'] ?? 0, 2, ',', '.') }}).
            </p>
        @endif
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Total Simpanan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($memberSummaries as $member)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <details class="loan-drop">
                                <summary>
                                    <div class="loan-drop-title">
                                        <span>{{ $member['name'] }}</span>
                                        <span class="muted">({{ $member['member_no'] ?? '-' }})</span>
                                    </div>
                                    <span class="loan-drop-meta">{{ count($member['months']) }} bulan</span>
                                </summary>
                                <div class="loan-drop-body">
                                    @if(count($member['months']))
                                        <div class="loan-subcard">
                                            <table class="table table-compact table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Bulan</th>
                                                        @foreach($types as $label)
                                                            <th>{{ $label }}</th>
                                                        @endforeach
                                                        <th>Total</th>
                                                        @if($role === 'bendahara')
                                                            <th>Aksi</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($member['months'] as $month)
                                                        <tr>
                                                            <td>{{ $month['label'] }}</td>
                                                            @foreach($types as $key => $label)
                                                                <td>Rp {{ number_format($month['types'][$key] ?? 0, 2, ',', '.') }}</td>
                                                            @endforeach
                                                            <td>Rp {{ number_format($month['total'], 2, ',', '.') }}</td>
                                                            @if($role === 'bendahara')
                                                                <td>
                                                                    @if($month['editable'])
                                                                        <div class="icon-actions">
                                                                            <button
                                                                                class="icon-button"
                                                                                type="button"
                                                                                data-savings-edit
                                                                                data-user="{{ $member['id'] }}"
                                                                                data-user-name="{{ $member['name'] }}"
                                                                                data-month="{{ $month['key'] }}"
                                                                                data-label="{{ $month['label'] }}"
                                                                                data-types='@json($month["manual_types"] ?? [])'
                                                                                title="Edit simpanan"
                                                                            >
                                                                                @include('partials.icon', ['name' => 'edit'])
                                                                            </button>
                                                                            <form method="post" action="{{ route('savings.destroyMonth', ['user' => $member['id'], 'month' => $month['key']]) }}" onsubmit="return confirm('Hapus simpanan manual bulan ini?');">
                                                                                @csrf
                                                                                @method('delete')
                                                                                <button class="icon-button danger" type="submit" title="Hapus simpanan">
                                                                                    @include('partials.icon', ['name' => 'trash'])
                                                                                </button>
                                                                            </form>
                                                                        </div>
                                                                    @elseif(!empty($month['has_manual']))
                                                                        <span class="muted">Terkunci</span>
                                                                    @else
                                                                        <span class="muted">-</span>
                                                                    @endif
                                                                </td>
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="muted">Belum ada transaksi simpanan.</div>
                                    @endif
                                </div>
                            </details>
                        </td>
                        <td>Rp {{ number_format($member['total_amount'], 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Belum ada data simpanan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($role === 'bendahara')
        <dialog class="modal" id="savings-modal">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Input Simpanan</h3>
                        <p class="muted">Pilih anggota dan isi beberapa jenis simpanan sekaligus.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close>Keluar</button>
                </div>
                <form method="post" action="{{ route('savings.store') }}" class="form-grid">
                    @csrf
                    <div class="form-control">
                        <label>Pilih Anggota</label>
                        <select name="user_id" required>
                            <option value="">-- Pilih --</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->member_no ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label>Pilih Jenis Simpanan</label>
                        <div class="checkbox-grid">
                            @foreach($types as $key => $label)
                                @php
                                    $oldTypes = old('types', []);
                                    $isChecked = in_array($key, $oldTypes, true);
                                @endphp
                                <div class="checkbox-item">
                                    <label class="checkbox">
                                        <input
                                            type="checkbox"
                                            name="types[]"
                                            value="{{ $key }}"
                                            data-savings-type
                                            @if($isChecked) checked @endif
                                        >
                                        {{ $label }}
                                    </label>
                                    <input
                                        type="text"
                                        name="amounts[{{ $key }}]"
                                        placeholder="Nominal {{ $label }}"
                                        data-savings-amount="{{ $key }}"
                                        value="{{ old('amounts.' . $key) }}"
                                        @if(!$isChecked) disabled @endif
                                    >
                                </div>
                            @endforeach
                        </div>
                        <div class="muted">Centang jenis simpanan yang ingin diisi lalu isi nominalnya.</div>
                    </div>
                    <div class="form-control">
                        <label>Catatan</label>
                        <textarea name="note" placeholder="Opsional"></textarea>
                    </div>
                    <div class="modal-actions">
                        <button class="btn btn-ghost" type="button" data-modal-close>Batal</button>
                        <button class="btn btn-primary" type="submit">Simpan</button>
                    </div>
                </form>
            </div>
        </dialog>

        <dialog class="modal" id="savings-post-modal">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Masukkan Simpanan ke Arus Kas</h3>
                        <p class="muted">Unggah eviden untuk mencatat simpanan ke arus kas.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close-post>Keluar</button>
                </div>
                <form method="post" action="{{ route('savings.post') }}" class="form-grid" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="post_to_cash" value="1">
                    <div class="form-control">
                        <label>Eviden (jpg/png/pdf)</label>
                        <input type="file" name="evidence" accept=".jpg,.jpeg,.png,.pdf" required>
                        <div class="muted">Wajib diisi agar semua transaksi arus kas memiliki bukti.</div>
                    </div>
                    <div class="modal-actions">
                        <button class="btn btn-ghost" type="button" data-modal-close-post>Batal</button>
                        <button class="btn btn-primary" type="submit">Kirim ke Arus Kas</button>
                    </div>
                </form>
            </div>
        </dialog>

        <dialog class="modal" id="savings-edit-modal">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Ubah Simpanan Bulanan</h3>
                        <p class="muted" data-edit-subtitle>Pilih nominal simpanan yang akan diperbarui.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close-edit>Keluar</button>
                </div>
                <form method="post" data-edit-form data-action-base="{{ route('savings.updateMonth', ['user' => '__USER__', 'month' => '__MONTH__']) }}" class="form-grid">
                    @csrf
                    <div class="form-control">
                        <label>Nama Anggota</label>
                        <input type="text" data-edit-user readonly>
                    </div>
                    <div class="form-control">
                        <label>Periode</label>
                        <input type="text" data-edit-period readonly>
                    </div>
                    <div class="form-control">
                        <label>Jenis Simpanan</label>
                        <div class="checkbox-grid">
                            @foreach($types as $key => $label)
                                <div class="checkbox-item">
                                    <label class="checkbox">
                                        <input
                                            type="checkbox"
                                            name="types[]"
                                            value="{{ $key }}"
                                            data-edit-savings-type
                                        >
                                        {{ $label }}
                                    </label>
                                    <input
                                        type="text"
                                        name="amounts[{{ $key }}]"
                                        placeholder="Nominal {{ $label }}"
                                        data-edit-savings-amount="{{ $key }}"
                                        disabled
                                    >
                                </div>
                            @endforeach
                        </div>
                        <div class="muted">Hanya simpanan manual yang belum masuk arus kas bisa diubah.</div>
                    </div>
                    <div class="modal-actions">
                        <button class="btn btn-ghost" type="button" data-modal-close-edit>Batal</button>
                        <button class="btn btn-primary" type="submit">Simpan</button>
                    </div>
                </form>
            </div>
        </dialog>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('savings-modal');
                const postModal = document.getElementById('savings-post-modal');
                const openButtons = document.querySelectorAll('[data-modal-open="savings-modal"]');
                const openPostButtons = document.querySelectorAll('[data-modal-open="savings-post-modal"]');
                const closeButtons = modal ? modal.querySelectorAll('[data-modal-close]') : [];
                const closePostButtons = postModal ? postModal.querySelectorAll('[data-modal-close-post]') : [];
                const shouldOpen = @json($errors->any());
                const shouldOpenPost = @json(old('post_to_cash') ? true : false);

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
                };

                const openPostModal = () => {
                    if (!postModal) {
                        return;
                    }
                    if (typeof postModal.showModal === 'function') {
                        postModal.showModal();
                    } else {
                        postModal.setAttribute('open', 'open');
                    }
                };

                const closePostModal = () => {
                    if (!postModal) {
                        return;
                    }
                    if (typeof postModal.close === 'function') {
                        postModal.close();
                    } else {
                        postModal.removeAttribute('open');
                    }
                };

                openButtons.forEach((btn) => {
                    btn.addEventListener('click', openModal);
                });
                openPostButtons.forEach((btn) => {
                    btn.addEventListener('click', openPostModal);
                });

                closeButtons.forEach((btn) => {
                    btn.addEventListener('click', closeModal);
                });
                closePostButtons.forEach((btn) => {
                    btn.addEventListener('click', closePostModal);
                });

                if (modal) {
                    modal.addEventListener('click', (event) => {
                        if (event.target === modal) {
                            closeModal();
                        }
                    });
                }
                if (postModal) {
                    postModal.addEventListener('click', (event) => {
                        if (event.target === postModal) {
                            closePostModal();
                        }
                    });
                }

                document.querySelectorAll('[data-savings-type]').forEach((checkbox) => {
                    checkbox.addEventListener('change', () => {
                        const type = checkbox.value;
                        const amountInput = document.querySelector('[data-savings-amount="' + type + '"]');
                        if (!amountInput) {
                            return;
                        }
                        amountInput.disabled = !checkbox.checked;
                        if (!checkbox.checked) {
                            amountInput.value = '';
                        }
                        if (checkbox.checked) {
                            amountInput.focus();
                        }
                    });
                });

                if (shouldOpenPost) {
                    openPostModal();
                } else if (shouldOpen) {
                    openModal();
                }
            });
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const editModal = document.getElementById('savings-edit-modal');
                if (!editModal) {
                    return;
                }

                const editForm = editModal.querySelector('[data-edit-form]');
                const actionBase = editForm ? editForm.getAttribute('data-action-base') : '';
                const closeButtons = editModal.querySelectorAll('[data-modal-close-edit]');
                const userInput = editModal.querySelector('[data-edit-user]');
                const periodInput = editModal.querySelector('[data-edit-period]');

                const openEdit = () => {
                    if (typeof editModal.showModal === 'function') {
                        editModal.showModal();
                    } else {
                        editModal.setAttribute('open', 'open');
                    }
                };

                const closeEdit = () => {
                    if (typeof editModal.close === 'function') {
                        editModal.close();
                    } else {
                        editModal.removeAttribute('open');
                    }
                };

                const formatNumber = (value) => {
                    const digits = (value || '').toString().replace(/[^\d]/g, '');
                    if (!digits) {
                        return '';
                    }
                    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                };

                const resetEditFields = () => {
                    editModal.querySelectorAll('[data-edit-savings-type]').forEach((checkbox) => {
                        checkbox.checked = false;
                    });
                    editModal.querySelectorAll('[data-edit-savings-amount]').forEach((input) => {
                        input.value = '';
                        input.disabled = true;
                    });
                };

                document.querySelectorAll('[data-savings-edit]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const userId = button.getAttribute('data-user');
                        const userName = button.getAttribute('data-user-name');
                        const monthKey = button.getAttribute('data-month');
                        const monthLabel = button.getAttribute('data-label');
                        const types = JSON.parse(button.getAttribute('data-types') || '{}');

                        resetEditFields();

                        if (userInput) {
                            userInput.value = userName || '';
                        }
                        if (periodInput) {
                            periodInput.value = monthLabel || '';
                        }
                        if (editForm && actionBase) {
                            editForm.action = actionBase
                                .replace('__USER__', userId || '')
                                .replace('__MONTH__', monthKey || '');
                        }

                        editModal.querySelectorAll('[data-edit-savings-type]').forEach((checkbox) => {
                            const type = checkbox.value;
                            const amount = Number(types[type] || 0);
                            const amountInput = editModal.querySelector('[data-edit-savings-amount="' + type + '"]');
                            if (!amountInput) {
                                return;
                            }
                            if (amount > 0) {
                                checkbox.checked = true;
                                amountInput.disabled = false;
                                amountInput.value = formatNumber(Math.round(amount).toString());
                            }
                        });

                        openEdit();
                    });
                });

                editModal.querySelectorAll('[data-edit-savings-type]').forEach((checkbox) => {
                    checkbox.addEventListener('change', () => {
                        const type = checkbox.value;
                        const amountInput = editModal.querySelector('[data-edit-savings-amount="' + type + '"]');
                        if (!amountInput) {
                            return;
                        }
                        amountInput.disabled = !checkbox.checked;
                        if (!checkbox.checked) {
                            amountInput.value = '';
                        } else {
                            amountInput.focus();
                        }
                    });
                });

                closeButtons.forEach((btn) => {
                    btn.addEventListener('click', closeEdit);
                });

                editModal.addEventListener('click', (event) => {
                    if (event.target === editModal) {
                        closeEdit();
                    }
                });
            });
        </script>
    @endif

    @if(!in_array($role, ['bendahara_kantor', 'anggota']))
        <dialog class="modal" id="savings-rekap-modal">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Preview Rekap Simpanan (PDF)</h3>
                        <p class="muted">Pratinjau rekap simpanan dalam format PDF.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close-rekap>Keluar</button>
                </div>
                <div class="action-row" style="margin-bottom: 12px;">
                    <label class="muted" style="font-size: 13px;">Filter Bulan</label>
                    <select id="rekap-month-filter">
                        <option value="all">Semua</option>
                        @foreach($monthNames as $number => $label)
                            <option value="{{ $number }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <iframe class="pdf-preview" src="{{ route('savings.rekap.pdf') }}" title="Rekap Simpanan PDF" data-rekap-src="{{ route('savings.rekap.pdf') }}"></iframe>
            </div>
        </dialog>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('savings-rekap-modal');
                const monthFilter = document.getElementById('rekap-month-filter');
                const iframe = modal ? modal.querySelector('.pdf-preview') : null;
                const openButtons = document.querySelectorAll('[data-modal-open="savings-rekap-modal"]');
                const closeButtons = modal ? modal.querySelectorAll('[data-modal-close-rekap]') : [];

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
                };

                openButtons.forEach((btn) => {
                    btn.addEventListener('click', openModal);
                });

                closeButtons.forEach((btn) => {
                    btn.addEventListener('click', closeModal);
                });

                if (monthFilter && iframe) {
                    const baseUrl = iframe.getAttribute('data-rekap-src') || '';
                    monthFilter.addEventListener('change', () => {
                        const month = monthFilter.value || 'all';
                        const url = month === 'all' ? baseUrl : (baseUrl + '?month=' + month);
                        iframe.src = url;
                    });
                }

                if (modal) {
                    modal.addEventListener('click', (event) => {
                        if (event.target === modal) {
                            closeModal();
                        }
                    });
                }
            });
        </script>
    @endif
@endsection

