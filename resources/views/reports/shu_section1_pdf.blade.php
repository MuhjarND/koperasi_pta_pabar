<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan SHU - Bagian 1</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 20px 28px;
            color: #111;
        }
        h2, h3 {
            margin: 0 0 8px;
            text-align: center;
            text-transform: uppercase;
        }
        .meta {
            text-align: center;
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        th, td {
            border: 1px solid #222;
            padding: 4px 6px;
        }
        th {
            background: #f2f2f2;
            text-transform: uppercase;
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
        .section-title {
            font-weight: 700;
            margin: 8px 0 6px;
        }
    </style>
</head>
<body>
    <h2>Laporan SHU Simpanan</h2>
    <h3>Bagian 1 - Hasil Usaha dan Dagang</h3>
    <div class="meta">Periode: {{ $periodLabel }}</div>

    <div class="section-title">A. Hasil Usaha Simpan Pinjam</div>
    <table>
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

    <div class="section-title">B. Hasil Dagang (Laba per Bulan)</div>
    <table>
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
</body>
</html>
