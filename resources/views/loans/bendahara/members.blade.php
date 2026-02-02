@extends('layouts.app')

@section('title', 'Peserta Peminjaman')
@section('subtitle', 'Rekap pinjaman per anggota beserta detail angsuran.')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'users'])</div>
                <h3>Daftar Peserta Peminjaman</h3>
            </div>
        </div>
        <form method="get" class="action-row" style="margin-bottom: 16px;">
            <select name="month">
                <option value="">-- Semua Bulan --</option>
                @foreach($monthNames as $monthNumber => $monthLabel)
                    <option value="{{ $monthNumber }}" @if($selectedMonth == $monthNumber) selected @endif>
                        {{ $monthLabel }}
                    </option>
                @endforeach
            </select>
            <select name="year">
                <option value="">-- Semua Tahun --</option>
                @foreach($availableYears as $year)
                    <option value="{{ $year }}" @if($selectedYear == $year) selected @endif>
                        {{ $year }}
                    </option>
                @endforeach
            </select>
            <select name="settlement">
                <option value="">-- Status Pelunasan --</option>
                <option value="lunas" @if($selectedSettlement === 'lunas') selected @endif>Lunas</option>
                <option value="belum" @if($selectedSettlement === 'belum') selected @endif>Belum Lunas</option>
            </select>
            <select name="approval">
                <option value="">-- Status Approval --</option>
                @foreach($statusLabels as $key => $label)
                    <option value="{{ $key }}" @if($selectedApproval === $key) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Terapkan</button>
            <a class="btn btn-ghost" href="{{ route('bendahara.loans.members') }}">Reset</a>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>Nama Anggota</th>
                    <th>Status Pelunasan</th>
                    <th>Status Approval</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    <tr>
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
                                            $loanSettledClass = ($loan['settlement_label'] ?? '') === 'Lunas'
                                                ? 'status-pill--success'
                                                : 'status-pill--danger';
                                        @endphp
                                        <div class="loan-subcard">
                                            <div class="loan-subheader">
                                                <div>
                                                    <strong>Angsuran ({{ $loan['term_months'] }} bulan)</strong>
                                                    <div class="muted">
                                                        Total pinjaman Rp {{ number_format($loan['amount'], 2, ',', '.') }}
                                                    </div>
                                                </div>
                                                <a class="btn btn-ghost" href="{{ route('bendahara.loans.show', $loan['id']) }}">Detail</a>
                                            </div>
                                            <table class="table table-compact">
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
                                                    <tr>
                                                        <td colspan="2"><strong>Total</strong></td>
                                                        <td><strong>Rp {{ number_format($loan['principal_total'], 2, ',', '.') }}</strong></td>
                                                        <td><strong>Rp {{ number_format($loan['fee_total'], 2, ',', '.') }}</strong></td>
                                                        <td>
                                                            <strong>
                                                                <span class="status-pill {{ $loanSettledClass }}">
                                                                    {{ $loan['settlement_label'] }}
                                                                </span>
                                                            </strong>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
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
                            @php
                                $status = $member['latest_status'] ?? 'submitted';
                                $badge = $statusBadges[$status] ?? 'warning';
                            @endphp
                            <span class="badge {{ $badge }}">{{ $statusLabels[$status] ?? $status }}</span>
                        </td>
                        <td>
                            @if($member['latest_loan_id'])
                                <a class="btn btn-ghost" href="{{ route('bendahara.loans.show', $member['latest_loan_id']) }}">Detail</a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Belum ada data peminjaman.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

