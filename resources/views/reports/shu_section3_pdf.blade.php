<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan SHU - Bagian 3</title>
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
        }
        th, td {
            border: 1px solid #222;
            padding: 5px 6px;
        }
        th {
            background: #f2f2f2;
            text-transform: uppercase;
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <h2>Laporan SHU Simpanan</h2>
    <h3>Bagian 3 - Rekap SHU Anggota</h3>
    <div class="meta">Periode: {{ $periodLabel }}</div>

    <table>
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
                    <td class="text-right">{{ $row['shu_pinjaman'] > 0 ? number_format($row['shu_pinjaman'], 2, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $row['shu_partisipasi'] > 0 ? number_format($row['shu_partisipasi'], 2, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $shuTotal > 0 ? number_format($shuTotal, 2, ',', '.') : '-' }}</td>
                    <td>-</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Belum ada data SHU anggota.</td>
                </tr>
            @endforelse
            @if(!empty($shuMemberRows))
                <tr>
                    <td colspan="2"><strong>Total</strong></td>
                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['shu_pinjaman'], 2, ',', '.') }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['shu_partisipasi'], 2, ',', '.') }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($shuMemberTotals['shu_pinjaman'] + $shuMemberTotals['shu_partisipasi'], 2, ',', '.') }}</strong></td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
