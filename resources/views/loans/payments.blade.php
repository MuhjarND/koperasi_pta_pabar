@extends('layouts.app')

@section('title', 'Pembayaran Angsuran')
@section('subtitle', 'Catat pembayaran angsuran dan pantau statusnya.')

@section('content')
    @php
        $paymentStoreRoute = $isMember ? 'anggota.loans.payments.store' : 'bendahara.loans.payments.store';
        $filterRoute = $isMember ? route('anggota.loans.payments') : route('bendahara.loans.payments');
    @endphp

    @if($isMember)
        <div class="summary-grid" style="margin-bottom: 16px;">
            <div class="summary-item accent">
                <div class="label">Total Pinjaman</div>
                <div class="value">{{ isset($memberSummary['loans']) ? count($memberSummary['loans']) : 0 }} pinjaman</div>
            </div>
            <div class="summary-item">
                <div class="label">Total Terbayar</div>
                <div class="value">Rp {{ number_format($memberSummary['total_paid_amount'] ?? 0, 2, ',', '.') }}</div>
            </div>
            <div class="summary-item warning">
                <div class="label">Sisa Tagihan</div>
                @php
                    $dueAmount = $memberSummary['total_due_amount'] ?? 0;
                    $paidAmount = $memberSummary['total_paid_amount'] ?? 0;
                    $remainingAmount = max($dueAmount - $paidAmount, 0);
                @endphp
                <div class="value">Rp {{ number_format($remainingAmount, 2, ',', '.') }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Angsuran</div>
                <div class="value">
                    {{ $memberSummary['settled_installments'] ?? 0 }}/{{ $memberSummary['total_installments'] ?? 0 }} lunas
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'wallet'])</div>
                <h3>Rekap Peminjaman</h3>
            </div>
            @if(!in_array($role, ['anggota', 'bendahara_kantor']))
                <div class="action-row">
                    <button class="btn btn-ghost" type="button" data-modal-open="loans-rekap-modal">Preview Rekap PDF</button>
                </div>
            @endif
        </div>
        <form method="get" action="{{ $filterRoute }}" class="action-row" style="margin-bottom: 16px;">
            <input type="search" name="q" value="{{ $search ?? '' }}" placeholder="Cari nama anggota...">
            <select name="status">
                <option value="">-- Semua Status --</option>
                <option value="lunas" @if(($statusFilter ?? '') === 'lunas') selected @endif>Lunas</option>
                <option value="belum" @if(($statusFilter ?? '') === 'belum') selected @endif>Belum Lunas</option>
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
            <a class="btn btn-ghost" href="{{ $filterRoute }}">Reset</a>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Anggota</th>
                    <th>Status Angsuran</th>
                    <th>Total Terbayar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    @php
                        $rowNumber = $loop->iteration;
                    @endphp
                    <tr>
                        <td>{{ $rowNumber }}</td>
                        <td>
                            <details class="loan-drop">
                                <summary>
                                    <div class="loan-drop-title">
                                        <span>{{ $member['name'] }}</span>
                                        <span class="muted">({{ $member['member_no'] ?? '-' }})</span>
                                    </div>
                                    <span class="loan-drop-meta">{{ count($member['loans']) }} pinjaman</span>
                                </summary>
                                <div class="loan-drop-body">
                                    @forelse($member['loans'] as $loan)
                                        @php
                                            $loanSettledClass = $loan['remaining_count'] === 0
                                                ? 'status-pill--success'
                                                : 'status-pill--danger';
                                        @endphp
                                        <div class="loan-subcard">
                                            <div class="loan-subheader">
                                                <div>
                                                    <strong>Angsuran ({{ $loan['term_months'] }} bulan)</strong>
                                                    <div class="muted">Total pinjaman Rp {{ number_format($loan['amount'], 2, ',', '.') }}</div>
                                                    <div class="loan-submeta">
                                                        <span class="status-pill {{ $loanSettledClass }}">
                                                            {{ $loan['paid_count'] }}/{{ $loan['term_months'] }} lunas
                                                        </span>
                                                        <span class="muted">Sisa {{ $loan['remaining_count'] }} bulan</span>
                                                    </div>
                                                </div>
                                                <div class="loan-subactions">
                                                    @if(!empty($loan['settlement_request']))
                                                        <div class="status-stack">
                                                            <span class="status-pill status-pill--danger">Menunggu Validasi Pelunasan</span>
                                                            @if(!$isMember)
                                                                <form method="post" action="{{ route('bendahara.loans.payments.settlement.approve', $loan['settlement_request']->id) }}">
                                                                    @csrf
                                                                    <button class="btn btn-primary" type="submit">Validasi Pelunasan</button>
                                                                </form>
                                                                @if(!empty($loan['settlement_request']->evidence_path))
                                                                    <a class="link-short" href="{{ asset($loan['settlement_request']->evidence_path) }}" target="_blank" rel="noopener">Lihat Eviden</a>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    @elseif(!empty($loan['payment_options']))
                                                        <button
                                                            class="btn btn-primary"
                                                            type="button"
                                                            data-open-payment
                                                            data-loan-id="{{ $loan['id'] }}"
                                                            data-loan-label="{{ $member['name'] }} ({{ $member['member_no'] ?? '-' }})"
                                                            data-options='@json($loan['payment_options'])'
                                                            data-remaining-principal="{{ $loan['remaining_principal_total'] }}"
                                                            data-remaining-fee="{{ $loan['remaining_fee_total'] }}"
                                                        >
                                                            Tambah Pembayaran
                                                        </button>
                                                    @else
                                                        <span class="status-pill status-pill--success">Lunas</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <table class="table table-compact table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Bulan</th>
                                                        <th>Tanggal</th>
                                                        <th>Pokok</th>
                                                        <th>Jasa</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($loan['installments'] as $installment)
                                                        @php
                                                            $installmentClass = ($installment['status'] ?? '') === 'Lunas'
                                                                ? 'status-pill--success'
                                                                : 'status-pill--danger';
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $installment['month'] }}</td>
                                                            <td>{{ $installment['date'] }}</td>
                                                            <td>Rp {{ number_format($installment['principal'], 2, ',', '.') }}</td>
                                                            <td>Rp {{ number_format($installment['fee'], 2, ',', '.') }}</td>
                                                            <td>
                                                                <span class="status-pill {{ $installmentClass }}">
                                                                    {{ $installment['status'] }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>

                                            <div class="loan-history">
                                                <div class="loan-history-title">Riwayat Pembayaran</div>
                                                <table class="table table-compact table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Angsuran</th>
                                                            <th>Tanggal</th>
                                                            <th>Pokok</th>
                                                            <th>Jasa</th>
                                                            <th>Eviden</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($loan['payments'] as $payment)
                                                            <tr>
                                                                <td>{{ $payment->installment_no }}</td>
                                                                <td>{{ $payment->paid_at }}</td>
                                                                <td>Rp {{ number_format($payment->amount_principal, 2, ',', '.') }}</td>
                                                                <td>Rp {{ number_format($payment->amount_fee, 2, ',', '.') }}</td>
                                                                <td>
                                                                    @if(!empty($payment->evidence_path))
                                                                        <a class="link-short" href="{{ asset($payment->evidence_path) }}" target="_blank" rel="noopener">Lihat</a>
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5">Belum ada pembayaran.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="muted">Belum ada pinjaman.</div>
                                    @endforelse
                                </div>
                            </details>
                        </td>
                        <td>
                            <div class="status-stack">
                                <span class="status-pill status-pill--success">
                                    Lunas: {{ $member['settled_installments'] }}/{{ $member['total_installments'] }}
                                </span>
                                <span class="status-pill status-pill--danger">
                                    Belum Lunas: {{ $member['unsettled_installments'] }}/{{ $member['total_installments'] }}
                                </span>
                            </div>
                        </td>
                        <td>
                            Rp {{ number_format($member['total_paid_amount'], 2, ',', '.') }}
                            / Rp {{ number_format($member['total_due_amount'] ?? 0, 2, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Belum ada data pembayaran.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(!in_array($role, ['anggota', 'bendahara_kantor']))
        <dialog class="modal" id="loans-rekap-modal">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Preview Rekap Peminjaman (PDF)</h3>
                        <p class="muted">Pratinjau rekap peminjaman dalam format PDF.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close-rekap>Keluar</button>
                </div>
                <div class="action-row" style="margin-bottom: 12px;">
                    <label class="muted" style="font-size: 13px;">Filter Bulan</label>
                    <select id="loans-rekap-month-filter">
                        <option value="all">Semua</option>
                        @foreach($monthNames as $number => $label)
                            <option value="{{ $number }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <iframe class="pdf-preview" src="{{ route('loans.rekap.pdf') }}" title="Rekap Peminjaman PDF" data-rekap-src="{{ route('loans.rekap.pdf') }}"></iframe>
            </div>
        </dialog>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('loans-rekap-modal');
                const monthFilter = document.getElementById('loans-rekap-month-filter');
                const iframe = modal ? modal.querySelector('.pdf-preview') : null;
                const openButtons = document.querySelectorAll('[data-modal-open="loans-rekap-modal"]');
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

    <dialog class="modal" id="payment-modal">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h3>Input Pembayaran</h3>
                    <p class="muted">Untuk <span id="modal-loan-label">-</span></p>
                </div>
                <button class="btn btn-ghost" type="button" data-modal-close>Keluar</button>
            </div>
            <form method="post" action="{{ route($paymentStoreRoute) }}" class="form-grid" enctype="multipart/form-data" id="payment-form">
                @csrf
                <input type="hidden" name="loan_id" id="modal-loan-id" value="{{ old('loan_id') }}">
                <div class="form-control">
                    <label>Pilih Angsuran</label>
                    <select name="installment_no" id="modal-installment-select" required>
                        <option value="">-- Pilih --</option>
                    </select>
                </div>
                @if($isMember)
                    <div class="form-control">
                        <label class="checkbox">
                            <input type="checkbox" name="is_settlement" id="modal-is-settlement" value="1" @if(old('is_settlement')) checked @endif>
                            Pelunasan (lunas semua angsuran)
                        </label>
                        <div class="muted">Butuh validasi bendahara sebelum dianggap lunas.</div>
                    </div>
                @endif
                <div class="form-control">
                    <label>Tanggal Pembayaran</label>
                    <input type="date" name="paid_at" value="{{ old('paid_at', date('Y-m-d')) }}" required>
                </div>
                <div class="form-control">
                    <label>Nominal Pokok</label>
                    <input type="number" step="0.01" min="0" name="amount_principal" id="modal-amount-principal" value="{{ old('amount_principal') }}" required>
                </div>
                <div class="form-control">
                    <label>Nominal Jasa</label>
                    <input type="number" step="0.01" min="0" name="amount_fee" id="modal-amount-fee" value="{{ old('amount_fee') }}" required>
                </div>
                <div class="form-control">
                    <label>Validasi Nominal</label>
                    <div id="payment-check" class="status-pill status-pill--success">Sesuai dengan perhitungan.</div>
                    <div class="muted" id="payment-check-detail"></div>
                </div>
                <div class="form-control">
                    <label>Eviden Pembayaran (jpg/png/pdf)</label>
                    <input type="file" name="evidence" accept=".jpg,.jpeg,.png,.pdf" required>
                    <div class="muted">Eviden wajib untuk semua transaksi arus kas.</div>
                </div>
                <div class="form-control">
                    <label>Catatan</label>
                    <textarea name="note" placeholder="Opsional">{{ old('note') }}</textarea>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-ghost" type="button" data-modal-close>Batal</button>
                    <button class="btn btn-primary" type="submit">Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('payment-modal');
            const openButtons = document.querySelectorAll('[data-open-payment]');
            const closeButtons = modal ? modal.querySelectorAll('[data-modal-close]') : [];
            const select = document.getElementById('modal-installment-select');
            const form = document.getElementById('payment-form');
            const loanIdInput = document.getElementById('modal-loan-id');
            const principalInput = document.getElementById('modal-amount-principal');
            const feeInput = document.getElementById('modal-amount-fee');
            const loanLabel = document.getElementById('modal-loan-label');
            const settlementToggle = document.getElementById('modal-is-settlement');
            const checkBadge = document.getElementById('payment-check');
            const checkDetail = document.getElementById('payment-check-detail');
            const isMember = @json($isMember);
            const shouldOpen = @json($errors->any());
            const lastLoanId = @json(old('loan_id'));
            const oldInstallment = @json(old('installment_no'));
            const oldSettlement = @json(old('is_settlement'));
            let allowAutoFill = !shouldOpen;
            let remainingPrincipal = 0;
            let remainingFee = 0;

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

            const fillOptions = (options) => {
                if (!select) {
                    return;
                }
                select.innerHTML = '<option value="">-- Pilih --</option>';
                options.forEach((option) => {
                    const item = document.createElement('option');
                    item.value = option.no;
                    item.dataset.principal = option.principal;
                    item.dataset.fee = option.fee;
                    item.textContent = option.label;
                    select.appendChild(item);
                });
            };

            const updateAmounts = () => {
                if (!allowAutoFill || !select) {
                    return;
                }
                if (settlementToggle && settlementToggle.checked) {
                    principalInput.value = formatInputNumber(remainingPrincipal || 0);
                    feeInput.value = formatInputNumber(remainingFee || 0);
                    updateCheckBadge();
                    return;
                }
                const option = select.options[select.selectedIndex];
                if (!option) {
                    return;
                }
                const principal = option.getAttribute('data-principal');
                const fee = option.getAttribute('data-fee');
                if (principal !== null) {
                    principalInput.value = formatInputNumber(principal);
                }
                if (fee !== null) {
                    feeInput.value = formatInputNumber(fee);
                }
                updateCheckBadge();
            };

            const formatInputNumber = (value) => {
                const digits = String(value ?? '').replace(/[^\d]/g, '');
                if (!digits) {
                    return '';
                }
                return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            };

            const parseInputNumber = (value) => {
                const digits = String(value ?? '').replace(/[^\d]/g, '');
                return digits ? Number(digits) : 0;
            };

            const getExpectedAmounts = () => {
                if (settlementToggle && settlementToggle.checked) {
                return {
                    principal: Math.round(Number(remainingPrincipal || 0)),
                    fee: Math.round(Number(remainingFee || 0)),
                };
            }

                if (!select) {
                    return null;
                }

                const option = select.options[select.selectedIndex];
                if (!option) {
                    return null;
                }

                return {
                    principal: Math.round(Number(option.getAttribute('data-principal') || 0)),
                    fee: Math.round(Number(option.getAttribute('data-fee') || 0)),
                };
            };

            const formatCurrency = (value) => {
                return Number(value || 0).toLocaleString('id-ID');
            };

            const updateCheckBadge = () => {
                if (!checkBadge || !checkDetail) {
                    return;
                }

                const expected = getExpectedAmounts();
                if (!expected) {
                    checkBadge.textContent = 'Pilih angsuran terlebih dahulu.';
                    checkBadge.classList.remove('status-pill--success');
                    checkBadge.classList.add('status-pill--danger');
                    checkDetail.textContent = '';
                    return;
                }

                const inputPrincipal = parseInputNumber(principalInput.value);
                const inputFee = parseInputNumber(feeInput.value);
                const matchPrincipal = Math.abs(inputPrincipal - expected.principal) < 0.01;
                const matchFee = Math.abs(inputFee - expected.fee) < 0.01;

                if (matchPrincipal && matchFee) {
                    checkBadge.textContent = 'Sesuai dengan perhitungan.';
                    checkBadge.classList.remove('status-pill--danger');
                    checkBadge.classList.add('status-pill--success');
                } else {
                    checkBadge.textContent = 'Belum sesuai, periksa kembali.';
                    checkBadge.classList.remove('status-pill--success');
                    checkBadge.classList.add('status-pill--danger');
                }

                checkDetail.textContent = 'Perhitungan: Pokok Rp ' + formatCurrency(expected.principal)
                    + ' + Jasa Rp ' + formatCurrency(expected.fee);
            };

            const toggleSettlementState = () => {
                if (!settlementToggle || !select) {
                    return;
                }
                const isSettlement = settlementToggle.checked;
                select.disabled = isSettlement;
                select.required = !isSettlement;
                if (isSettlement) {
                    select.value = '';
                } else if (!select.value && select.options.length > 1) {
                    select.value = select.options[1].value;
                }
                updateAmounts();
            };

            const openPayment = (button) => {
                const options = JSON.parse(button.dataset.options || '[]');
                fillOptions(options);
                loanIdInput.value = button.dataset.loanId || '';
                loanLabel.textContent = button.dataset.loanLabel || '-';
                remainingPrincipal = Number(button.dataset.remainingPrincipal || 0);
                remainingFee = Number(button.dataset.remainingFee || 0);

                if (settlementToggle) {
                    settlementToggle.checked = shouldOpen && !!oldSettlement;
                }

                if (shouldOpen && oldInstallment) {
                    select.value = oldInstallment;
                } else if (options.length) {
                    select.value = options[0].no;
                }

                toggleSettlementState();
                updateAmounts();
                openModal();
            };

            openButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    allowAutoFill = true;
                    openPayment(btn);
                });
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

            if (select) {
                select.addEventListener('change', () => {
                    allowAutoFill = true;
                    updateAmounts();
                });
            }

            if (settlementToggle) {
                settlementToggle.addEventListener('change', () => {
                    allowAutoFill = true;
                    toggleSettlementState();
                });
            }

            if (principalInput) {
                principalInput.addEventListener('input', updateCheckBadge);
            }
            if (feeInput) {
                feeInput.addEventListener('input', updateCheckBadge);
            }

            if (form && !isMember) {
                form.addEventListener('submit', (event) => {
                    const expected = getExpectedAmounts();
                    if (!expected) {
                        return;
                    }
                    const inputPrincipal = parseInputNumber(principalInput.value);
                    const inputFee = parseInputNumber(feeInput.value);
                    const matchPrincipal = Math.abs(inputPrincipal - expected.principal) < 0.01;
                    const matchFee = Math.abs(inputFee - expected.fee) < 0.01;

                    if (!matchPrincipal || !matchFee) {
                        const proceed = window.confirm('Nominal belum sesuai perhitungan. Periksa kembali sebelum menyimpan.');
                        if (!proceed) {
                            event.preventDefault();
                        }
                    }
                });
            }

            if (shouldOpen && lastLoanId) {
                const trigger = document.querySelector('[data-open-payment][data-loan-id="' + lastLoanId + '"]');
                if (trigger) {
                    openPayment(trigger);
                } else {
                    openModal();
                }
            }
        });
    </script>
@endsection

