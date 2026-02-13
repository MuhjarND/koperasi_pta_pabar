@extends('layouts.app')

@section('title', 'Dashboard Anggota')
@section('subtitle', 'Pantau simpanan dan angsuran Anda, serta ajukan pinjaman baru.')

@section('content')
    @php
        $badgeClass = config('koperasi.status_badges');
    @endphp

    <div class="stat-grid">
        <div class="stat stat--mint">
            <div class="stat-icon">
                @include('partials.icon', ['name' => 'wallet'])
            </div>
            <div>
                <div>Saldo Koperasi</div>
                <div class="value">Rp {{ number_format($balance, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'coins'])</div>
                <h3>Rekap Simpanan & Pemotongan</h3>
            </div>
            <a class="btn btn-ghost" href="{{ route('savings.index') }}">Lihat Detail</a>
        </div>
        <div class="grid-two">
            <div class="loan-subcard">
                <div class="loan-subheader">
                    <div>
                        <strong>Simpanan Saya</strong>
                        <div class="muted">{{ $savingsSummary['name'] }} ({{ $savingsSummary['member_no'] ?? '-' }})</div>
                    </div>
                </div>
                <div class="summary-grid">
                    <div class="summary-item accent">
                        <div class="label">Total Simpanan</div>
                        <div class="value">Rp {{ number_format($savingsSummary['total_amount'], 2, ',', '.') }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Bulan Tercatat</div>
                        <div class="value">{{ count($savingsSummary['months']) }} bulan</div>
                    </div>
                </div>
                <details class="loan-drop" style="margin-top: 12px;">
                    <summary>
                        <div class="loan-drop-title">
                            <span>Rincian Bulanan Simpanan</span>
                        </div>
                        <span class="loan-drop-meta">Klik untuk melihat detail</span>
                    </summary>
                    <div class="loan-drop-body">
                        @if(count($savingsSummary['months']))
                            <div class="loan-subcard">
                                <table class="table table-compact table-striped">
                                    <thead>
                                        <tr>
                                            <th>Bulan</th>
                                            @foreach($savingsTypes as $label)
                                                <th>{{ $label }}</th>
                                            @endforeach
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($savingsSummary['months'] as $month)
                                            <tr>
                                                <td>{{ $month['label'] }}</td>
                                                @foreach($savingsTypes as $key => $label)
                                                    <td>Rp {{ number_format($month['types'][$key] ?? 0, 2, ',', '.') }}</td>
                                                @endforeach
                                                <td>Rp {{ number_format($month['total'], 2, ',', '.') }}</td>
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
            </div>

            <div class="loan-subcard">
                <div class="loan-subheader">
                    <div>
                        <strong>Pemotongan Gaji Saya</strong>
                        <div class="muted">Rekap potongan simpanan & angsuran Anda.</div>
                    </div>
                </div>
                <div class="summary-grid">
                    <div class="summary-item accent">
                        <div class="label">Total Pemotongan</div>
                        <div class="value">Rp {{ number_format($deductionSummary['total'] ?? 0, 2, ',', '.') }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Simpanan</div>
                        <div class="value">Rp {{ number_format($deductionSummary['savings_total'] ?? 0, 2, ',', '.') }}</div>
                    </div>
                    <div class="summary-item warning">
                        <div class="label">Angsuran</div>
                        <div class="value">Rp {{ number_format(($deductionSummary['principal_total'] ?? 0) + ($deductionSummary['fee_total'] ?? 0), 2, ',', '.') }}</div>
                    </div>
                </div>
                <details class="loan-drop" style="margin-top: 12px;">
                    <summary>
                        <div class="loan-drop-title">
                            <span>Rincian Bulanan Pemotongan</span>
                        </div>
                        <span class="loan-drop-meta">Klik untuk melihat detail</span>
                    </summary>
                    <div class="loan-drop-body">
                        @if(!empty($deductionSummary['months']))
                            <div class="loan-subcard">
                                <table class="table table-compact table-striped">
                                    <thead>
                                        <tr>
                                            <th>Bulan</th>
                                            @foreach($savingsTypes as $label)
                                                <th>{{ $label }}</th>
                                            @endforeach
                                            <th>Angsuran Pokok</th>
                                            <th>Angsuran Jasa</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($deductionSummary['months'] as $month)
                                            <tr>
                                                <td>{{ $month['label'] }}</td>
                                                @foreach($savingsTypes as $key => $label)
                                                    <td>Rp {{ number_format($month['types'][$key] ?? 0, 2, ',', '.') }}</td>
                                                @endforeach
                                                <td>Rp {{ number_format($month['principal'] ?? 0, 2, ',', '.') }}</td>
                                                <td>Rp {{ number_format($month['fee'] ?? 0, 2, ',', '.') }}</td>
                                                <td>Rp {{ number_format($month['total'] ?? 0, 2, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="muted">Belum ada pemotongan gaji.</div>
                        @endif
                    </div>
                </details>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'history'])</div>
                <h3>Pengajuan Terbaru</h3>
            </div>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Nominal</th>
                    <th>Tenor</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentLoans as $loan)
                    <tr>
                        <td>Rp {{ number_format($loan->amount, 2, ',', '.') }}</td>
                        <td>{{ $loan->term_months }} bulan</td>
                        <td>
                            <span class="badge {{ $badgeClass[$loan->status] ?? '' }}">
                                {{ $statusLabels[$loan->status] ?? $loan->status }}
                            </span>
                        </td>
                        <td>{{ $loan->created_at }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Belum ada pengajuan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="grid-two" style="margin-top: 16px;">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'wallet'])</div>
                    <h3>Status Angsuran</h3>
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Pinjaman</th>
                        <th>Tenor</th>
                        <th>Terbayar</th>
                        <th>Sisa</th>
                        <th>Status</th>
                        <th>Terakhir Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loanStats as $loan)
                        @php
                            $paidCount = (int) $loan->paid_count;
                            $termMonths = (int) $loan->term_months;
                            $remaining = max($termMonths - $paidCount, 0);
                            $settled = $termMonths > 0 && $remaining === 0;
                            $settledClass = $settled ? 'status-pill--success' : 'status-pill--danger';
                        @endphp
                        <tr>
                            <td>Rp {{ number_format($loan->amount, 2, ',', '.') }}</td>
                            <td>{{ $termMonths }} bulan</td>
                            <td>{{ $paidCount }}x</td>
                            <td>{{ $remaining }}x</td>
                            <td>
                                <span class="status-pill {{ $settledClass }}">
                                    {{ $settled ? 'Lunas' : 'Belum Lunas' }}
                                </span>
                            </td>
                            <td>{{ $loan->last_paid_at ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Belum ada pembayaran angsuran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'clipboard'])</div>
                    <h3>Riwayat Pembayaran</h3>
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Pinjaman</th>
                        <th>Angsuran</th>
                        <th>Tanggal</th>
                        <th>Pokok</th>
                        <th>Jasa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $payment)
                        <tr>
                            <td>Rp {{ number_format($payment->amount, 2, ',', '.') }}</td>
                            <td>{{ $payment->installment_no }}</td>
                            <td>{{ $payment->paid_at }}</td>
                            <td>Rp {{ number_format($payment->amount_principal, 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($payment->amount_fee, 2, ',', '.') }}</td>
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
@endsection

