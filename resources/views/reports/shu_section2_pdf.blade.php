<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan SHU - Bagian 2</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 18px 24px;
            color: #111;
        }
        h2, h3 {
            margin: 0 0 8px;
            text-align: center;
            text-transform: uppercase;
        }
        .meta {
            text-align: center;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #222;
            padding: 4px 5px;
        }
        th {
            background: #f2f2f2;
            text-transform: uppercase;
            font-size: 9px;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <h2>Laporan SHU Simpanan</h2>
    <h3>Bagian 2 - SHU Simpanan Total</h3>
    <div class="meta">Periode: {{ $periodLabel }}</div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Nama Anggota</th>
                <th colspan="3">Hasil Usaha Unit</th>
                <th rowspan="2">Biaya Operasional 25%</th>
                <th colspan="6">SHU Simpanan (Dari HU - Biaya Operasional)</th>
            </tr>
            <tr>
                <th>Toko</th>
                <th>SP-Pinjam</th>
                <th>Jumlah</th>
                <th>Dana Pengurus 15%</th>
                <th>Dana Cadangan 15%</th>
                <th>Dana Sosial 10%</th>
                <th>Dana Pendidikan 5%</th>
                <th>SHU Simpanan 30%</th>
                <th>SHU Partisipasi 25%</th>
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
                    <td class="text-right">{{ $row['shu_pinjaman'] > 0 ? number_format($row['shu_pinjaman'], 2, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $row['shu_partisipasi'] > 0 ? number_format($row['shu_partisipasi'], 2, ',', '.') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12">Belum ada data SHU simpanan.</td>
                </tr>
            @endforelse
            @if(!empty($shuMemberRows))
                <tr>
                    <td colspan="2"><strong>Total</strong></td>
                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['toko'], 2, ',', '.') }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['sp_pinjam'], 2, ',', '.') }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['hu'], 2, ',', '.') }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['operational'], 2, ',', '.') }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['dana_pengurus'], 2, ',', '.') }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['dana_cadangan'], 2, ',', '.') }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['dana_sosial'], 2, ',', '.') }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['dana_pendidikan'], 2, ',', '.') }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['shu_pinjaman'], 2, ',', '.') }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['shu_partisipasi'], 2, ',', '.') }}</strong></td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
