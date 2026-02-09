@extends('layouts.app')

@section('title', 'Laporan ' . $typeLabel)
@section('subtitle', 'Periode: ' . $periodLabel)

@section('content')
    <div class="report-shell report-shell--full">
        <div class="report-main">
            @if($type === 'shu')
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-icon">@include('partials.icon', ['name' => 'chart'])</div>
                            <h3>Laporan SHU Simpanan dan Partisipasi Usaha</h3>
                        </div>
                    </div>
                    <div class="loan-drop" style="margin-bottom: 16px;">
                        <details open>
                            <summary>
                                <div class="loan-drop-title">
                                    <strong>1. Hasil Usaha dan Dagang</strong>
                                    <span class="loan-drop-meta">Rincian hasil usaha simpan pinjam & laba dagang</span>
                                </div>
                            </summary>
                            <div class="loan-drop-body">
                                <div class="action-row" style="margin-bottom: 12px;">
                                    <button class="btn btn-ghost" type="button" data-modal-open="shu-pdf-1">Preview PDF</button>
                                </div>
                                <h3 class="report-section-title">A. Hasil Usaha Simpan Pinjam</h3>
                                <div class="report-table-wrap">
                                    <table class="table report-table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama</th>
                                            @foreach($serviceMonths as $month)
                                                <th>{{ strtoupper($month['label']) }}</th>
                                            @endforeach
                                            <th>Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($memberServiceRows as $memberRow)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $memberRow['name'] }}</td>
                                                @foreach($memberRow['months'] as $amount)
                                                    <td class="text-right">
                                                        @if($amount > 0)
                                                            {{ number_format($amount, 2, ',', '.') }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td class="text-right">
                                                    @if($memberRow['total'] > 0)
                                                        {{ number_format($memberRow['total'], 2, ',', '.') }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ 3 + count($serviceMonths) }}">Belum ada data jasa pinjaman.</td>
                                            </tr>
                                        @endforelse
                                        @if(!empty($monthlyServiceIncome))
                                            <tr>
                                                <td colspan="2"><strong>Total</strong></td>
                                                @foreach($monthlyServiceIncome as $row)
                                                    <td class="text-right"><strong>{{ number_format($row['amount'], 2, ',', '.') }}</strong></td>
                                                @endforeach
                                                <td class="text-right"><strong>{{ number_format($serviceIncome, 2, ',', '.') }}</strong></td>
                                            </tr>
                                        @endif
                                    </tbody>
                                    </table>
                                </div>
                                <h3 class="report-section-title" style="margin-top: 16px;">B. Hasil Dagang (Laba per Bulan)</h3>
                                <div class="report-table-wrap">
                                    <table class="table report-table">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama</th>
                                                @foreach($serviceMonths as $month)
                                                    <th>{{ strtoupper($month['label']) }}</th>
                                                @endforeach
                                                <th>Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($memberTradeRows as $memberRow)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $memberRow['name'] }}</td>
                                                    @foreach($memberRow['months'] as $amount)
                                                        <td class="text-right">
                                                            @if($amount > 0)
                                                                {{ number_format($amount, 2, ',', '.') }}
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                    <td class="text-right">
                                                        @if($memberRow['total'] > 0)
                                                            {{ number_format($memberRow['total'], 2, ',', '.') }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="{{ 3 + count($serviceMonths) }}">Belum ada data laba dagang.</td>
                                                </tr>
                                            @endforelse
                                            @if(!empty($monthlyTradeTotals))
                                                <tr>
                                                    <td colspan="2"><strong>Total</strong></td>
                                                    @foreach($monthlyTradeTotals as $row)
                                                        <td class="text-right"><strong>{{ number_format($row['amount'], 2, ',', '.') }}</strong></td>
                                                    @endforeach
                                                    <td class="text-right"><strong>{{ number_format($tradeTotal, 2, ',', '.') }}</strong></td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </details>
                    </div>

                    <div class="loan-drop" style="margin-bottom: 16px;">
                        <details>
                            <summary>
                                <div class="loan-drop-title">
                                    <strong>2. SHU Total</strong>
                                    <span class="loan-drop-meta">Rincian pembagian hasil usaha</span>
                                </div>
                            </summary>
                            <div class="loan-drop-body">
                                <div class="action-row" style="margin-bottom: 12px;">
                                    <button class="btn btn-ghost" type="button" data-modal-open="shu-pdf-2">Preview PDF</button>
                                </div>
                                <div class="report-table-wrap">
                                    <table class="table report-table">
                                    <thead>
                                        <tr>
                                            <th rowspan="2">No</th>
                                            <th rowspan="2">Nama Anggota</th>
                                            <th colspan="3">Hasil Usaha Unit</th>
                                            <th rowspan="2">Biaya Operasional 25% (Dari HU)</th>
                                            <th colspan="6">SHU 45% dibagi untuk</th>
                                        </tr>
                                        <tr>
                                            <th class="text-right">Toko</th>
                                            <th class="text-right">SP-PINJAM</th>
                                            <th class="text-right">Jumlah</th>
                                            <th>Dana Pengurus 15%</th>
                                            <th>Dana Cadangan 15%</th>
                                            <th>Dana Sosial 10%</th>
                                            <th>Dana Pendidikan 5%</th>
                                            <th class="text-right shu-highlight">SHU Simpanan 30%</th>
                                            <th class="text-right shu-highlight">SHU Partisipasi 25%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($shuMemberRows as $row)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $row['name'] }}</td>
                                                <td class="text-right">{{ $row['toko'] > 0 ? number_format($row['toko'], 2, ',', '.') : '-' }}</td>
                                                <td class="text-right">{{ $row['sp_pinjam'] > 0 ? number_format($row['sp_pinjam'], 2, ',', '.') : '-' }}</td>
                                                <td class="text-right">{{ $row['jumlah'] > 0 ? number_format($row['jumlah'], 2, ',', '.') : '-' }}</td>
                                                <td class="text-right">{{ $row['operational'] > 0 ? number_format($row['operational'], 2, ',', '.') : '-' }}</td>
                                                <td class="text-right">{{ $row['dana_pengurus'] > 0 ? number_format($row['dana_pengurus'], 2, ',', '.') : '-' }}</td>
                                                <td class="text-right">{{ $row['dana_cadangan'] > 0 ? number_format($row['dana_cadangan'], 2, ',', '.') : '-' }}</td>
                                                <td class="text-right">{{ $row['dana_sosial'] > 0 ? number_format($row['dana_sosial'], 2, ',', '.') : '-' }}</td>
                                                <td class="text-right">{{ $row['dana_pendidikan'] > 0 ? number_format($row['dana_pendidikan'], 2, ',', '.') : '-' }}</td>
                                                <td class="text-right shu-highlight">
                                                    {{ $row['shu_pinjaman'] > 0 ? number_format($row['shu_pinjaman'], 2, ',', '.') : '-' }}
                                                </td>
                                                <td class="text-right shu-highlight">
                                                    {{ $row['shu_partisipasi'] > 0 ? number_format($row['shu_partisipasi'], 2, ',', '.') : '-' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                            <td colspan="11">Belum ada data SHU simpanan.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    @if(!empty($shuMemberRows))
                                        <tfoot>
                                            <tr class="report-total">
                                                <td colspan="2"><strong>Total</strong></td>
                                                <td class="text-right"><strong>{{ number_format($shuMemberTotals['toko'], 2, ',', '.') }}</strong></td>
                                                <td class="text-right"><strong>{{ number_format($shuMemberTotals['sp_pinjam'], 2, ',', '.') }}</strong></td>
                                                <td class="text-right"><strong>{{ number_format($shuMemberTotals['hu'], 2, ',', '.') }}</strong></td>
                                                <td class="text-right"><strong>{{ number_format($shuMemberTotals['operational'], 2, ',', '.') }}</strong></td>
                                                <td class="text-right"><strong>{{ number_format($shuMemberTotals['dana_pengurus'], 2, ',', '.') }}</strong></td>
                                                <td class="text-right"><strong>{{ number_format($shuMemberTotals['dana_cadangan'], 2, ',', '.') }}</strong></td>
                                                <td class="text-right"><strong>{{ number_format($shuMemberTotals['dana_sosial'], 2, ',', '.') }}</strong></td>
                                                <td class="text-right"><strong>{{ number_format($shuMemberTotals['dana_pendidikan'], 2, ',', '.') }}</strong></td>
                                                <td class="text-right shu-highlight"><strong>{{ number_format($shuMemberTotals['shu_pinjaman'], 2, ',', '.') }}</strong></td>
                                                <td class="text-right shu-highlight"><strong>{{ number_format($shuMemberTotals['shu_partisipasi'], 2, ',', '.') }}</strong></td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                    </table>
                                </div>
                                <p class="muted">Dasar perhitungan: total hasil jasa - biaya operasional.</p>
                            </div>
                        </details>
                    </div>

                    <div class="loan-drop">
                        <details>
                            <summary>
                                <div class="loan-drop-title">
                                    <strong>3. Rekap SHU Anggota</strong>
                                    <span class="loan-drop-meta">SHU simpanan dan partisipasi usaha per anggota</span>
                                </div>
                            </summary>
                            <div class="loan-drop-body">
                                <div class="action-row" style="margin-bottom: 12px;">
                                    <button class="btn btn-ghost" type="button" data-modal-open="shu-pdf-3">Preview PDF</button>
                                </div>
                                <div class="report-table-wrap">
                                    <table class="table report-table">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Anggota</th>
                                                <th class="text-right">SHU Simpanan</th>
                                                <th class="text-right">SHU Partisipasi</th>
                                                <th class="text-right">Jumlah</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($shuMemberRows as $row)
                                                @php
                                                    $shuTotal = (float) ($row['shu_pinjaman'] + $row['shu_partisipasi']);
                                                @endphp
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $row['name'] }}</td>
                                                    <td class="text-right">
                                                        {{ $row['shu_pinjaman'] > 0 ? number_format($row['shu_pinjaman'], 2, ',', '.') : '-' }}
                                                    </td>
                                                    <td class="text-right">
                                                        {{ $row['shu_partisipasi'] > 0 ? number_format($row['shu_partisipasi'], 2, ',', '.') : '-' }}
                                                    </td>
                                                    <td class="text-right">{{ $shuTotal > 0 ? number_format($shuTotal, 2, ',', '.') : '-' }}</td>
                                                    <td>-</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6">Belum ada data SHU anggota.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        @if(!empty($shuMemberRows))
                                            <tfoot>
                                                <tr class="report-total">
                                                    <td colspan="2"><strong>Total</strong></td>
                                                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['shu_pinjaman'], 2, ',', '.') }}</strong></td>
                                                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['shu_partisipasi'], 2, ',', '.') }}</strong></td>
                                                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['shu_pinjaman'] + $shuMemberTotals['shu_partisipasi'], 2, ',', '.') }}</strong></td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            @elseif($type === 'laba-rugi')
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-icon">@include('partials.icon', ['name' => 'chart'])</div>
                            <h3>Laporan Laba Rugi</h3>
                        </div>
                        <div class="action-row">
                            <button class="btn btn-ghost" type="button" data-modal-open="laba-rugi-pdf">Preview PDF</button>
                        </div>
                    </div>
                    <table class="table report-table">
                        <tbody>
                            <tr>
                                <td><strong>I. KEGIATAN UNIT USAHA</strong></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>&bull; Pendapatan Bagi Hasil Usaha Simpan Pinjam</td>
                                <td class="text-right">Rp {{ number_format($serviceIncome, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>&bull; Pendapatan Lain-lain</td>
                                <td class="text-right">Rp {{ number_format($otherIncomeTotal ?? 0, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>&bull; Hasil Partisipasi Usaha Toko</td>
                                <td class="text-right">Rp {{ number_format($tradeTotal ?? 0, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Jumlah Pendapatan / Hasil Usaha</strong></td>
                                <td class="text-right"><strong>Rp {{ number_format($totalIncome, 2, ',', '.') }}</strong></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td><strong>II. BIAYA-BIAYA</strong></td>
                                <td></td>
                            </tr>
                            @forelse($expenseItems as $item)
                                <tr>
                                    <td>&bull; {{ $item['name'] }}</td>
                                    <td class="text-right">Rp {{ number_format($item['amount'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td>&bull; Tidak ada biaya</td>
                                    <td class="text-right">Rp 0,00</td>
                                </tr>
                            @endforelse
                            <tr>
                                <td><strong>Jumlah Biaya Operasional</strong></td>
                                <td class="text-right"><strong>Rp {{ number_format($expenseTotal, 2, ',', '.') }}</strong></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td><strong>III. LABA BERSIH HASIL USAHA</strong></td>
                                <td class="text-right"><strong>Rp {{ number_format($totalIncome - $expenseTotal, 2, ',', '.') }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="muted">Laba bersih dihitung dari jumlah pendapatan dikurangi biaya operasional.</p>
                </div>
            @elseif($type === 'arus-kas')
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-icon">@include('partials.icon', ['name' => 'wallet'])</div>
                            <h3>Laporan Arus Kas</h3>
                        </div>
                    </div>
                    <table class="table">
                        <tbody>
                            <tr>
                                <td>Arus Kas Operasional</td>
                                <td>Rp {{ number_format($operatingCash, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Arus Kas Investasi</td>
                                <td>Rp {{ number_format($investingCash, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Arus Kas Pendanaan</td>
                                <td>Rp {{ number_format($financingCash, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Netto Arus Kas</strong></td>
                                <td><strong>Rp {{ number_format($netCashFlow, 2, ',', '.') }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="muted">Arus kas operasional menghitung simpanan dan penjualan.</p>
                </div>
            @elseif($type === 'neraca')
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-icon">@include('partials.icon', ['name' => 'wallet'])</div>
                            <h3>Neraca (Posisi Keuangan)</h3>
                        </div>
                    </div>
                    <div class="grid-two" style="align-items: start;">
                        <div>
                            <h4 style="margin-top: 0;">Aktiva</h4>
                            <table class="table table-compact">
                                <tbody>
                                    <tr>
                                        <td colspan="2"><strong>I. Aktiva Lancar</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Total Kas Saat Ini</td>
                                        <td>Rp {{ number_format($cashBalance, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Total Piutang Anggota</td>
                                        <td>Rp {{ number_format($loanReceivable, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Kas Dagang</td>
                                        <td>Rp {{ number_format($tradeCash, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Persediaan Dagang</td>
                                        <td>Rp {{ number_format($inventoryValue, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Jumlah Aktiva Lancar</strong></td>
                                        <td><strong>Rp {{ number_format($currentAssetsTotal, 2, ',', '.') }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><strong>II. Penyertaan</strong></td>
                                    </tr>
                                    @forelse($investmentItems as $item)
                                        <tr>
                                            <td>{{ $item['name'] }}</td>
                                            <td>Rp {{ number_format($item['amount'], 2, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="muted">Belum ada data penyertaan.</td>
                                        </tr>
                                    @endforelse
                                    <tr>
                                        <td><strong>Jumlah Penyertaan</strong></td>
                                        <td><strong>Rp {{ number_format($investmentTotal, 2, ',', '.') }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><strong>III. Aktiva Tetap</strong></td>
                                    </tr>
                                    @forelse($inventoryItems as $item)
                                        <tr>
                                            <td>{{ $item['name'] }}</td>
                                            <td>Rp {{ number_format($item['amount'], 2, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="muted">Belum ada data inventaris.</td>
                                        </tr>
                                    @endforelse
                                    <tr>
                                        <td><strong>Jumlah Aktiva Tetap</strong></td>
                                        <td><strong>Rp {{ number_format($inventoryTotal, 2, ',', '.') }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Jumlah Total Aktiva</strong></td>
                                        <td><strong>Rp {{ number_format($totalAssets, 2, ',', '.') }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <h4 style="margin-top: 0;">Pasiva</h4>
                            <table class="table table-compact">
                                <tbody>
                                    <tr>
                                        <td colspan="2"><strong>IV. Pasiva Lancar</strong></td>
                                    </tr>
                                    @foreach($pasivaLancarItems as $item)
                                        <tr>
                                            <td>{{ $item['name'] }}</td>
                                            <td>Rp {{ number_format($item['amount'], 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td><strong>Jumlah Pasiva Lancar</strong></td>
                                        <td><strong>Rp {{ number_format($pasivaLancarTotal, 2, ',', '.') }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><strong>V. Modal Sendiri</strong></td>
                                    </tr>
                                    @foreach($modalSendiriItems as $item)
                                        <tr>
                                            <td>{{ $item['name'] }}</td>
                                            <td>Rp {{ number_format($item['amount'], 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td><strong>Jumlah Modal Sendiri</strong></td>
                                        <td><strong>Rp {{ number_format($modalSendiriTotal, 2, ',', '.') }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Jumlah Total Pasiva</strong></td>
                                        <td><strong>Rp {{ number_format($pasivaTotal, 2, ',', '.') }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <p class="muted">Aktiva dihitung dari saldo kas, piutang anggota, kas dagang, persediaan, penyertaan, dan inventaris.</p>
                </div>
            @elseif($type === 'ekuitas')
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-icon">@include('partials.icon', ['name' => 'users'])</div>
                            <h3>Perubahan Ekuitas</h3>
                        </div>
                    </div>
                    <table class="table">
                        <tbody>
                            <tr>
                                <td>Modal Awal</td>
                                <td>Rp {{ number_format($equityItems[0]['amount'] ?? 0, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>SHU Berjalan</td>
                                <td>Rp {{ number_format($shu, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Ekuitas Akhir</strong></td>
                                <td><strong>Rp {{ number_format(($equityItems[0]['amount'] ?? 0) + $shu, 2, ',', '.') }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="muted">Komponen modal dihitung dari aset dan liabilitas koperasi.</p>
                </div>
            @elseif($type === 'simpanan')
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-icon">@include('partials.icon', ['name' => 'coins'])</div>
                            <h3>Ringkasan Simpanan</h3>
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
                                    <td>Rp {{ number_format($savingsByType[$key] ?? 0, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif($type === 'pinjaman')
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-icon">@include('partials.icon', ['name' => 'clipboard'])</div>
                            <h3>Rekap Pinjaman</h3>
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(config('koperasi.status_labels') as $status => $label)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td>{{ $loanCounts[$status] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="muted">Pengajuan ditolak: Rp {{ number_format($loanRejected, 2, ',', '.') }}</p>
                </div>
            @elseif($type === 'jurnal')
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-icon">@include('partials.icon', ['name' => 'history'])</div>
                            <h3>Jurnal Transaksi</h3>
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Sumber</th>
                                <th>Keterangan</th>
                                <th>Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($journal as $entry)
                                <tr>
                                    <td>{{ $entry->date }}</td>
                                    <td>{{ $entry->source }}</td>
                                    <td>{{ $entry->description ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ $entry->direction === 'out' ? 'danger' : 'success' }}">
                                            Rp {{ number_format($entry->amount, 2, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">Belum ada transaksi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>

    @if($type === 'shu')
        <dialog class="modal" id="shu-pdf-1">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Laporan SHU - Bagian 1</h3>
                        <p class="muted">Format landscape.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close="shu-pdf-1">Keluar</button>
                </div>
                <iframe class="pdf-preview" src="{{ route('reports.shu.pdf', ['section' => 1, 'period' => $period, 'month' => $month, 'year' => $year]) }}"></iframe>
            </div>
        </dialog>

        <dialog class="modal" id="shu-pdf-2">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Laporan SHU - Bagian 2</h3>
                        <p class="muted">Format landscape.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close="shu-pdf-2">Keluar</button>
                </div>
                <iframe class="pdf-preview" src="{{ route('reports.shu.pdf', ['section' => 2, 'period' => $period, 'month' => $month, 'year' => $year]) }}"></iframe>
            </div>
        </dialog>

        <dialog class="modal" id="shu-pdf-3">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Laporan SHU - Bagian 3</h3>
                        <p class="muted">Format portrait.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close="shu-pdf-3">Keluar</button>
                </div>
                <iframe class="pdf-preview" src="{{ route('reports.shu.pdf', ['section' => 3, 'period' => $period, 'month' => $month, 'year' => $year]) }}"></iframe>
            </div>
        </dialog>

        <script>
            (function () {
                const modals = {
                    'shu-pdf-1': document.getElementById('shu-pdf-1'),
                    'shu-pdf-2': document.getElementById('shu-pdf-2'),
                    'shu-pdf-3': document.getElementById('shu-pdf-3')
                };

                const openButtons = document.querySelectorAll('[data-modal-open]');
                const closeButtons = document.querySelectorAll('[data-modal-close]');

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
                        const target = btn.getAttribute('data-modal-close');
                        closeModal(modals[target]);
                    });
                });

                Object.values(modals).forEach((modal) => {
                    if (!modal) {
                        return;
                    }
                    modal.addEventListener('click', (event) => {
                        if (event.target === modal) {
                            closeModal(modal);
                        }
                    });
                });
            })();
        </script>
    @endif

    @if($type === 'laba-rugi')
        <dialog class="modal" id="laba-rugi-pdf">
            <div class="modal-card">
                <div class="modal-header">
                    <div>
                        <h3>Laporan Laba Rugi</h3>
                        <p class="muted">Format portrait.</p>
                    </div>
                    <button class="btn btn-ghost" type="button" data-modal-close="laba-rugi-pdf">Keluar</button>
                </div>
                <iframe class="pdf-preview" src="{{ route('reports.laba-rugi.pdf') }}"></iframe>
            </div>
        </dialog>

        <script>
            (function () {
                const modal = document.getElementById('laba-rugi-pdf');
                const openButton = document.querySelector('[data-modal-open="laba-rugi-pdf"]');
                const closeButton = document.querySelector('[data-modal-close="laba-rugi-pdf"]');

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

                if (openButton) {
                    openButton.addEventListener('click', openModal);
                }
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
    @endif
@endsection
