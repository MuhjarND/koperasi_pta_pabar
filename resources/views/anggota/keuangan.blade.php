@extends('layouts.app')

@section('title', 'Simpanan & Pemotongan')
@section('subtitle', 'Ringkasan simpanan dan pemotongan gaji Anda.')

@section('content')
    <div class="stacked-cards">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'coins'])</div>
                    <h3>Simpanan Saya</h3>
                </div>
                <a class="btn btn-ghost" href="{{ route('savings.index') }}">Detail Simpanan</a>
            </div>

            <div class="summary-grid" style="margin-bottom: 12px;">
                <div class="summary-item accent">
                    <div class="label">Total Simpanan</div>
                    <div class="value">Rp {{ number_format($savingsSummary['total_amount'] ?? 0, 2, ',', '.') }}</div>
                </div>
                <div class="summary-item">
                    <div class="label">Simpanan Pokok</div>
                    <div class="value">Rp {{ number_format($savingsTypeTotals['pokok'] ?? 0, 2, ',', '.') }}</div>
                </div>
                <div class="summary-item">
                    <div class="label">Simpanan Wajib</div>
                    <div class="value">Rp {{ number_format($savingsTypeTotals['wajib'] ?? 0, 2, ',', '.') }}</div>
                </div>
                <div class="summary-item">
                    <div class="label">Simpanan Sukarela</div>
                    <div class="value">Rp {{ number_format($savingsTypeTotals['sukarela'] ?? 0, 2, ',', '.') }}</div>
                </div>
                <div class="summary-item warning">
                    <div class="label">Bulan Tercatat</div>
                    <div class="value">{{ count($savingsSummary['months'] ?? []) }} bulan</div>
                </div>
            </div>

            <details class="loan-drop">
                <summary>
                    <div class="loan-drop-title">
                        <span>Rincian Bulanan Simpanan</span>
                        <span class="muted">({{ $savingsSummary['name'] ?? 'Anggota' }})</span>
                    </div>
                    <span class="loan-drop-meta">Klik untuk melihat detail</span>
                </summary>
                <div class="loan-drop-body">
                    @if(!empty($savingsSummary['months']))
                        @foreach($savingsSummary['months'] as $month)
                            <div class="loan-subcard" style="margin-bottom: 12px;">
                                <div class="loan-subheader">
                                    <strong>{{ $month['label'] }}</strong>
                                    <span class="badge">Total Rp {{ number_format($month['total'], 2, ',', '.') }}</span>
                                </div>
                                <table class="table table-compact table-striped">
                                    <thead>
                                        <tr>
                                            @foreach($savingsTypes as $label)
                                                <th>{{ $label }}</th>
                                            @endforeach
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            @foreach($savingsTypes as $key => $label)
                                                <td>Rp {{ number_format($month['types'][$key] ?? 0, 2, ',', '.') }}</td>
                                            @endforeach
                                            <td><strong>Rp {{ number_format($month['total'], 2, ',', '.') }}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    @else
                        <div class="muted">Belum ada transaksi simpanan.</div>
                    @endif
                </div>
            </details>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <div class="card-icon">@include('partials.icon', ['name' => 'clipboard'])</div>
                    <h3>Pemotongan Gaji Saya</h3>
                </div>
                <a class="btn btn-ghost" href="{{ route('deductions.index') }}">Detail Pemotongan</a>
            </div>

            <div class="summary-grid" style="margin-bottom: 12px;">
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
                <div class="summary-item">
                    <div class="label">Bulan Tercatat</div>
                    <div class="value">{{ count($deductionSummary['months'] ?? []) }} bulan</div>
                </div>
            </div>

            <details class="loan-drop">
                <summary>
                    <div class="loan-drop-title">
                        <span>Rincian Bulanan Pemotongan</span>
                    </div>
                    <span class="loan-drop-meta">Klik untuk melihat detail</span>
                </summary>
                <div class="loan-drop-body">
                    @if(!empty($deductionSummary['months']))
                        @foreach($deductionSummary['months'] as $month)
                            <div class="loan-subcard" style="margin-bottom: 12px;">
                                <div class="loan-subheader">
                                    <strong>{{ $month['label'] }}</strong>
                                    <span class="badge">Total Rp {{ number_format($month['total'] ?? 0, 2, ',', '.') }}</span>
                                </div>
                                <table class="table table-compact table-striped">
                                    <thead>
                                        <tr>
                                            @foreach($savingsTypes as $label)
                                                <th>{{ $label }}</th>
                                            @endforeach
                                            <th>Angsuran Pokok</th>
                                            <th>Angsuran Jasa</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            @foreach($savingsTypes as $key => $label)
                                                <td>Rp {{ number_format($month['types'][$key] ?? 0, 2, ',', '.') }}</td>
                                            @endforeach
                                            <td>Rp {{ number_format($month['principal'] ?? 0, 2, ',', '.') }}</td>
                                            <td>Rp {{ number_format($month['fee'] ?? 0, 2, ',', '.') }}</td>
                                            <td><strong>Rp {{ number_format($month['total'] ?? 0, 2, ',', '.') }}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    @else
                        <div class="muted">Belum ada pemotongan gaji.</div>
                    @endif
                </div>
            </details>
        </div>
    </div>
@endsection
