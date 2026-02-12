@extends('layouts.app')

@section('title', 'Rekap Pemotongan')
@section('subtitle', 'Pemotongan gaji untuk simpanan dan angsuran per anggota.')

@section('content')
    @if($canVerify)
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'check'])</div>
                    <h3>Verifikasi Pemotongan</h3>
                </div>
            </div>
            <form method="post" action="{{ route('deductions.verify.all') }}" enctype="multipart/form-data" class="action-row" style="margin-bottom: 12px; justify-content: space-between; align-items: end;">
                @csrf
                <div>
                    <div class="muted">Validasi semua data pending sekaligus dengan satu eviden.</div>
                    <div class="muted">Total pending: {{ $pendingLogs->count() }} data | Total nominal: Rp {{ number_format((float) $pendingLogs->sum('total_amount'), 2, ',', '.') }}</div>
                </div>
                <div class="action-row">
                    <input type="file" name="evidence" accept=".jpg,.jpeg,.png,.pdf" required>
                    <button class="btn btn-primary" type="submit" @if($pendingLogs->isEmpty()) disabled @endif>Validasi Seluruh</button>
                </div>
            </form>
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Periode</th>
                        <th>Nominal</th>
                        <th>Eviden</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingLogs as $log)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $log->name }} ({{ $log->member_no ?? '-' }})</td>
                            <td>{{ $log->month }}/{{ $log->year }}</td>
                            <td>Rp {{ number_format($log->total_amount ?? 0, 2, ',', '.') }}</td>
                            <td>
                                <form method="post" action="{{ route('deductions.verify', $log->id) }}" enctype="multipart/form-data" class="action-row">
                                    @csrf
                                    <input type="file" name="evidence" accept=".jpg,.jpeg,.png,.pdf" required>
                                    <button class="btn btn-primary" type="submit">Verifikasi</button>
                                </form>
                            </td>
                            <td>
                                <span class="status-pill status-pill--danger">Pending</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Tidak ada pemotongan yang menunggu verifikasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="height: 16px;"></div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'clipboard'])</div>
                <h3>Rekap Pemotongan</h3>
            </div>
            @if($role === 'bendahara')
                <div class="action-row">
                    <button class="btn btn-primary" type="button" data-modal-open="deduction-modal">Atur Pemotongan</button>
                    <button class="btn btn-ghost" type="button" data-modal-open="deduction-rekap-modal">Preview Rekap PDF</button>
                </div>
            @elseif(!in_array($role, ['bendahara_kantor', 'anggota']))
                <div class="action-row">
                    <button class="btn btn-ghost" type="button" data-modal-open="deduction-rekap-modal">Preview Rekap PDF</button>
                </div>
            @endif
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Status Angsuran</th>
                    @if($role === 'bendahara')
                        <th>Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
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
                                                        <th>Angsuran Pokok</th>
                                                        <th>Angsuran Jasa</th>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($member['months'] as $month)
                                                        <tr>
                                                            <td>{{ $month['label'] }}</td>
                                                            @foreach($types as $key => $label)
                                                                <td>Rp {{ number_format($month['savings'][$key] ?? 0, 2, ',', '.') }}</td>
                                                            @endforeach
                                                            <td>Rp {{ number_format($month['loan_principal'] ?? 0, 2, ',', '.') }}</td>
                                                            <td>Rp {{ number_format($month['loan_fee'] ?? 0, 2, ',', '.') }}</td>
                                                            <td>Rp {{ number_format($month['total'] ?? 0, 2, ',', '.') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="muted">Belum ada data pemotongan.</div>
                                    @endif
                                </div>
                            </details>
                        </td>
                        <td>
                            <span class="status-pill {{ $member['remaining_installments'] > 0 ? 'status-pill--danger' : 'status-pill--success' }}">
                                Sisa {{ $member['remaining_installments'] }} bulan
                            </span>
                        </td>
                        @if($role === 'bendahara')
                            <td>
                                <div class="icon-actions">
                                    <button
                                        class="icon-button"
                                        type="button"
                                        data-deduction-edit="{{ $member['id'] }}"
                                        title="Edit pemotongan"
                                    >
                                        @include('partials.icon', ['name' => 'edit'])
                                    </button>
                                    <form method="post" action="{{ route('deductions.destroy', $member['id']) }}" onsubmit="return confirm('Hapus pengaturan pemotongan pegawai ini?');">
                                        @csrf
                                        @method('delete')
                                        <button class="icon-button danger" type="submit" title="Hapus pemotongan">
                                            @include('partials.icon', ['name' => 'trash'])
                                        </button>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $role === 'bendahara' ? 4 : 3 }}">Belum ada data pemotongan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($role === 'bendahara')
        <dialog class="modal" id="deduction-modal">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Atur Pemotongan Pegawai</h3>
                        <p class="muted">Pilih pegawai dan tentukan nominal pemotongan bulanan.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close>Keluar</button>
                </div>
                <form method="post" action="{{ route('deductions.store') }}" class="form-grid">
                    @csrf
                    <div class="form-control">
                        <label>Pilih Pegawai</label>
                        <select name="user_id" required>
                            <option value="">-- Pilih --</option>
                            @foreach($membersList as $member)
                                <option value="{{ $member->id }}" @if((string) old('user_id') === (string) $member->id) selected @endif>
                                    {{ $member->name }} ({{ $member->member_no ?? '-' }})
                                </option>
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
                                            data-deduction-type
                                            @if($isChecked) checked @endif
                                        >
                                        {{ $label }}
                                    </label>
                                    <input
                                        type="text"
                                        name="amounts[{{ $key }}]"
                                        placeholder="Nominal {{ $label }}"
                                        data-deduction-amount="{{ $key }}"
                                        value="{{ old('amounts.' . $key) }}"
                                        @if(!$isChecked) disabled @endif
                                    >
                                </div>
                            @endforeach
                        </div>
                        <div class="muted">Centang jenis simpanan yang ingin dipotong lalu isi nominalnya.</div>
                    </div>
                    <div class="form-control">
                        <label>Angsuran Pokok (otomatis)</label>
                        <input type="text" name="installment_pokok" value="{{ old('installment_pokok') }}" data-installment-principal readonly>
                    </div>
                    <div class="form-control">
                        <label>Angsuran Jasa (otomatis)</label>
                        <input type="text" name="installment_jasa" value="{{ old('installment_jasa') }}" data-installment-fee readonly>
                    </div>
                    <div class="muted">Nilai angsuran otomatis dari pinjaman aktif pegawai.</div>
                    <div class="modal-actions">
                        <button class="btn btn-ghost" type="button" data-modal-close>Batal</button>
                        <button class="btn btn-primary" type="submit">Simpan</button>
                    </div>
                </form>
            </div>
        </dialog>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('deduction-modal');
                const openButtons = document.querySelectorAll('[data-modal-open="deduction-modal"]');
                const closeButtons = modal ? modal.querySelectorAll('[data-modal-close]') : [];
                const shouldOpen = @json($errors->any());
                const deductionDefaults = @json($deductionDefaults ?? []);
                const installmentMap = @json($installmentMap ?? []);
                const userSelect = modal ? modal.querySelector('select[name="user_id"]') : null;

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

                openButtons.forEach((btn) => {
                    btn.addEventListener('click', openModal);
                });

                closeButtons.forEach((btn) => {
                    btn.addEventListener('click', closeModal);
                });

                if (modal) {
                    modal.addEventListener('click', (event) => {
                        if (event.target === modal) {
                            closeModal();
                        }
                    });
                }

                const formatNumber = (value) => {
                    const digits = (value || '').toString().replace(/[^\d]/g, '');
                    if (!digits) {
                        return '';
                    }
                    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                };

                const updateInstallments = () => {
                    if (!userSelect) {
                        return;
                    }
                    const selectedId = userSelect.value;
                    const data = installmentMap[selectedId] || { principal: 0, fee: 0 };
                    const principalInput = modal.querySelector('[data-installment-principal]');
                    const feeInput = modal.querySelector('[data-installment-fee]');

                    if (principalInput) {
                        if (!selectedId) {
                            principalInput.value = '';
                        } else {
                            principalInput.value = formatNumber(Math.round(data.principal || 0).toString());
                        }
                    }
                    if (feeInput) {
                        if (!selectedId) {
                            feeInput.value = '';
                        } else {
                            feeInput.value = formatNumber(Math.round(data.fee || 0).toString());
                        }
                    }
                };

                const updateDeductionAmounts = () => {
                    if (!userSelect) {
                        return;
                    }
                    const defaults = deductionDefaults[userSelect.value] || {};
                    document.querySelectorAll('[data-deduction-type]').forEach((checkbox) => {
                        const type = checkbox.value;
                        const amountInput = modal.querySelector('[data-deduction-amount="' + type + '"]');
                        if (!amountInput) {
                            return;
                        }
                        const value = Number(defaults[type] || 0);
                        const shouldCheck = value > 0;
                        checkbox.checked = shouldCheck;
                        amountInput.disabled = !shouldCheck;
                        amountInput.value = shouldCheck ? formatNumber(Math.round(value).toString()) : '';
                    });
                };

                document.querySelectorAll('[data-deduction-type]').forEach((checkbox) => {
                    checkbox.addEventListener('change', () => {
                        const type = checkbox.value;
                        const amountInput = modal.querySelector('[data-deduction-amount="' + type + '"]');
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

                document.querySelectorAll('[data-deduction-edit]').forEach((button) => {
                    button.addEventListener('click', () => {
                        if (!userSelect) {
                            return;
                        }
                        userSelect.value = button.getAttribute('data-deduction-edit');
                        updateDeductionAmounts();
                        updateInstallments();
                        openModal();
                    });
                });

                if (userSelect) {
                    userSelect.addEventListener('change', () => {
                        updateDeductionAmounts();
                        updateInstallments();
                    });
                }

                if (shouldOpen) {
                    openModal();
                    updateInstallments();
                } else {
                    updateDeductionAmounts();
                    updateInstallments();
                }
            });
        </script>
    @endif

    @if(!in_array($role, ['bendahara_kantor', 'anggota']))
        <dialog class="modal" id="deduction-rekap-modal">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Preview Rekap Pemotongan (PDF)</h3>
                        <p class="muted">Pratinjau rekap pemotongan dalam format PDF.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close-rekap>Keluar</button>
                </div>
                <div class="action-row" style="margin-bottom: 12px;">
                    <label class="muted" style="font-size: 13px;">Filter Bulan</label>
                    <select id="deduction-rekap-month-filter">
                        <option value="all">Semua</option>
                        @foreach($monthNames as $number => $label)
                            <option value="{{ $number }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <iframe class="pdf-preview" src="{{ route('deductions.rekap.pdf') }}" title="Rekap Pemotongan PDF" data-rekap-src="{{ route('deductions.rekap.pdf') }}"></iframe>
            </div>
        </dialog>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('deduction-rekap-modal');
                const monthFilter = document.getElementById('deduction-rekap-month-filter');
                const iframe = modal ? modal.querySelector('.pdf-preview') : null;
                const openButtons = document.querySelectorAll('[data-modal-open="deduction-rekap-modal"]');
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

