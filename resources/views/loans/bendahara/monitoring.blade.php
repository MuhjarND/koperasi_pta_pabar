@extends('layouts.app')

@section('title', 'Monitoring Peminjaman')
@section('subtitle', 'Pantau pengajuan sampai pencairan.')

@section('content')
    @php
        $formatDateTime = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-';
        $formatDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-';
    @endphp

    <div class="stat-grid" style="margin-bottom: 16px;">
        <div class="stat">
            <div class="stat-icon">@include('partials.icon', ['name' => 'file'])</div>
            <div>
                <div>Total Pengajuan</div>
                <div class="value">{{ number_format($summary['total'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="stat stat--mint">
            <div class="stat-icon">@include('partials.icon', ['name' => 'check'])</div>
            <div>
                <div>Menunggu Bendahara</div>
                <div class="value">{{ number_format($summary['treasurer_pending'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="stat">
            <div class="stat-icon">@include('partials.icon', ['name' => 'wallet'])</div>
            <div>
                <div>Menunggu Pencairan</div>
                <div class="value">{{ number_format($summary['pending_disbursement'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="stat stat--balance">
            <div class="stat-icon">@include('partials.icon', ['name' => 'coins'])</div>
            <div>
                <div>Dana Dicairkan</div>
                <div class="value">Rp {{ number_format($summary['disbursed_amount'], 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 16px;">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'history'])</div>
                <h3>Filter Monitoring</h3>
            </div>
        </div>
        <form method="get" action="{{ route('bendahara.loans.monitoring') }}" class="action-row">
            <select name="status">
                @foreach($statusOptions as $key => $label)
                    <option value="{{ $key }}" {{ $filters['status'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="year">
                <option value="">Semua Tahun</option>
                @foreach($availableYears as $year)
                    <option value="{{ $year }}" {{ (int) ($filters['year'] ?? 0) === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
            <a class="btn btn-ghost" href="{{ route('bendahara.loans.monitoring') }}">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <div class="card-icon">@include('partials.icon', ['name' => 'clipboard'])</div>
                <h3>Daftar Proses Pinjaman</h3>
            </div>
        </div>

        <div class="loan-monitor-list">
            @forelse($loans as $loan)
                <details class="loan-drop" {{ $loop->first ? 'open' : '' }}>
                    <summary>
                        <div class="loan-drop-title">
                            <strong>{{ $loan->name }}</strong>
                            @if($loan->member_no)
                                <span class="loan-drop-meta">{{ $loan->member_no }}</span>
                            @endif
                            <span class="status-pill {{ $loan->current_badge_class }}">{{ $loan->current_label }}</span>
                        </div>
                        <span class="loan-drop-meta">{{ $formatDate($loan->created_at) }}</span>
                    </summary>
                    <div class="loan-drop-body">
                        <div class="loan-monitor-progress" aria-hidden="true">
                            <span style="width: {{ $loan->progress_percent }}%;"></span>
                        </div>

                        <div class="loan-monitor-meta">
                            <div class="summary-item">
                                <span class="label">Nominal</span>
                                <span class="value">Rp {{ number_format($loan->amount, 2, ',', '.') }}</span>
                            </div>
                            <div class="summary-item">
                                <span class="label">Tenor</span>
                                <span class="value">{{ $loan->term_months }} bulan</span>
                            </div>
                            <div class="summary-item">
                                <span class="label">Tanggal Pengajuan</span>
                                <span class="value">{{ $formatDateTime($loan->created_at) }}</span>
                            </div>
                            <div class="summary-item">
                                <span class="label">Pencairan</span>
                                <span class="value">{{ $formatDateTime($loan->transfered_at) }}</span>
                            </div>
                        </div>

                        <div class="loan-subcard">
                            <div class="loan-subheader">
                                <div>
                                    <strong>Tujuan</strong>
                                    <div class="muted">{{ $loan->purpose ?: '-' }}</div>
                                </div>
                                <div class="loan-subactions">
                                    @if($loan->status === 'reviewed')
                                        <a class="btn btn-ghost" href="{{ route('bendahara.loans.show', $loan->id) }}">Review</a>
                                    @endif
                                    @if($loan->status === 'approved_chairman' && empty($loan->transfered_at) && empty($loan->transfer_evidence_path))
                                        <a class="btn btn-ghost" href="{{ route('bendahara.loans.disbursement') }}">Pencairan</a>
                                    @endif
                                    @if($loan->reminder_target)
                                        <form method="post" action="{{ route('bendahara.loans.monitoring.remind', $loan->id) }}">
                                            @csrf
                                            <button class="btn btn-upload" type="submit">Ingatkan WA</button>
                                        </form>
                                    @endif
                                    @if($loan->pdf_path)
                                        <a class="btn btn-ghost" href="{{ asset($loan->pdf_path) }}" target="_blank" rel="noopener">Form</a>
                                    @endif
                                    @if($loan->transfer_evidence_path)
                                        <a class="btn btn-ghost" href="{{ asset($loan->transfer_evidence_path) }}" target="_blank" rel="noopener">Bukti</a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="loan-monitor-steps">
                            @foreach($loan->process_steps as $step)
                                <div class="loan-monitor-step is-{{ $step['state'] }}">
                                    <div class="loan-monitor-step-head">
                                        <span class="loan-monitor-dot"></span>
                                        <strong>{{ $step['label'] }}</strong>
                                    </div>
                                    <div class="loan-monitor-step-meta">
                                        <span>{{ $step['actor'] ?: '-' }}</span>
                                        <span>{{ $formatDateTime($step['time']) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </details>
            @empty
                <div class="empty-state">Tidak ada data pinjaman pada filter ini.</div>
            @endforelse
        </div>
    </div>
@endsection
