<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Koperasi Konsumsi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 18px 24px;
        }
        .title {
            text-align: center;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .meta {
            text-align: center;
            margin-bottom: 14px;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #222;
            padding: 4px 6px;
        }
        th {
            background: #f0f0f0;
            text-align: center;
            font-weight: 700;
        }
        td {
            vertical-align: top;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 18px;
            display: table;
            width: 100%;
        }
        .footer-cell {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .qr {
            width: 90px;
            height: 90px;
            margin: 8px auto 6px;
        }
        .name {
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="title">Rekapitulasi Koperasi Konsumsi</div>
    <div class="meta">Dicetak: {{ $printedAt }}</div>

    <table>
        <thead>
            <tr>
                <th style="width: 28px;">No</th>
                <th>Nama Barang</th>
                <th style="width: 80px;">Jumlah Barang</th>
                <th style="width: 70px;">Modal</th>
                <th style="width: 85px;">Total Modal</th>
                <th style="width: 65px;">Penjualan</th>
                <th style="width: 75px;">HPP</th>
                <th style="width: 70px;">Harga Jual</th>
                <th style="width: 80px;">Jumlah</th>
                <th style="width: 70px;">Laba</th>
                <th style="width: 70px;">Sisa Stock</th>
                <th style="width: 80px;">Nilai Stock</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="text-center">{{ $row['total_qty'] }}</td>
                    <td class="text-right">{{ number_format($row['modal'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['total_modal'], 2, ',', '.') }}</td>
                    <td class="text-center">{{ $row['sold'] }}</td>
                    <td class="text-right">{{ number_format($row['hpp'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['price'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['revenue'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['profit'], 2, ',', '.') }}</td>
                    <td class="text-center">{{ $row['stock'] }}</td>
                    <td class="text-right">{{ number_format($row['stock_value'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center">Belum ada data produk.</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="4" class="text-right"><strong>Jumlah</strong></td>
                <td class="text-right"><strong>{{ number_format($totals['total_modal'], 2, ',', '.') }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($totals['hpp'], 2, ',', '.') }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($totals['revenue'], 2, ',', '.') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totals['profit'], 2, ',', '.') }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($totals['stock_value'], 2, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div class="footer-cell"></div>
        <div class="footer-cell">
            <div>Kasir</div>
            @if(!empty($qrImage))
                <img src="{{ $qrImage }}" alt="QR Kasir" class="qr">
            @endif
            <div class="name">({{ $cashierName }})</div>
        </div>
    </div>
</body>
</html>

