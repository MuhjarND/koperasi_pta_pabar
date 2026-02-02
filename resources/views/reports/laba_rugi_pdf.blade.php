<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Laba Rugi</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 18px 24px;
            color: #111;
        }
        h2, h3 {
            margin: 0 0 6px;
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
        }
        td, th {
            border: 1px solid #222;
            padding: 6px 8px;
        }
        .no-border td {
            border: none;
            padding: 4px 0;
        }
        .label {
            width: 65%;
        }
        .rp {
            width: 6%;
            text-align: center;
        }
        .amount {
            width: 29%;
            text-align: right;
        }
        .section-title {
            font-weight: 700;
        }
        .total-row td {
            font-weight: 700;
        }
        .spacer td {
            border: none;
            height: 8px;
            padding: 0;
        }
        .terbilang {
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <h2>Laporan Tahunan KPN As-Salam Pengadilan Tinggi Agama Papua Barat</h2>
    <h3>Perhitungan Rugi Laba pada Unit Simpan Pinjam</h3>
    <div class="meta">Periode {{ strtoupper($periodLabel) }}</div>

    <table>
        <tr>
            <td class="section-title label">I. KEGIATAN UNIT USAHA</td>
            <td class="rp"></td>
            <td class="amount"></td>
        </tr>
        <tr>
            <td class="label">&bull; Pendapatan Bagi Hasil Usaha Simpan Pinjam</td>
            <td class="rp">Rp</td>
            <td class="amount">{{ number_format($serviceIncome, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">&bull; Pendapatan Lain-lain</td>
            <td class="rp">Rp</td>
            <td class="amount">{{ number_format($otherIncomeTotal ?? 0, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">&bull; Pendapatan Bagi Hasil Unit Toko / Konsumsi</td>
            <td class="rp">Rp</td>
            <td class="amount">{{ number_format($tradeTotal ?? 0, 2, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td class="label">Jumlah Pendapatan / Hasil Usaha</td>
            <td class="rp">Rp</td>
            <td class="amount">{{ number_format($totalIncome, 2, ',', '.') }}</td>
        </tr>
        <tr class="spacer"><td colspan="3"></td></tr>
        <tr>
            <td class="section-title label">II. BIAYA - BIAYA</td>
            <td class="rp"></td>
            <td class="amount"></td>
        </tr>
        @forelse($expenseItems as $item)
            <tr>
                <td class="label">&bull; {{ $item['name'] }}</td>
                <td class="rp">Rp</td>
                <td class="amount">{{ number_format($item['amount'], 2, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td class="label">&bull; Tidak ada biaya</td>
                <td class="rp">Rp</td>
                <td class="amount">0,00</td>
            </tr>
        @endforelse
        <tr class="total-row">
            <td class="label">Jumlah Biaya Operasional</td>
            <td class="rp">Rp</td>
            <td class="amount">{{ number_format($expenseTotal, 2, ',', '.') }}</td>
        </tr>
        <tr class="spacer"><td colspan="3"></td></tr>
        <tr class="total-row">
            <td class="label section-title">III. LABA BERSIH HASIL USAHA</td>
            <td class="rp">Rp</td>
            <td class="amount">{{ number_format($labaBersih, 2, ',', '.') }}</td>
        </tr>
    </table>

    <div class="terbilang">
        Terbilang: {{ $labaBersihTerbilang }}
    </div>
</body>
</html>
