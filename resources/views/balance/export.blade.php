<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Arus Kas Bulanan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #333; padding: 6px 8px; }
        th { background: #d9ead3; text-align: center; }
        td { vertical-align: top; }
        .right { text-align: right; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <h3>Arus Kas Bulanan - {{ $monthNames[$month] ?? $month }} {{ $year }}</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Uraian</th>
                <th>Pokok</th>
                <th>Wajib</th>
                <th>Sukarela</th>
                <th>Angsuran Pokok</th>
                <th>Angsuran Jasa</th>
                <th>Lain-lain</th>
                <th>Jumlah Penerimaan</th>
                <th>Pengeluaran</th>
                <th>Saldo</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ledgerRows as $index => $row)
                @php
                    $receipts = $row['receipts'] ?? null;
                    $expensesTotal = (float) ($row['expenses_total'] ?? 0);
                    $descriptionItems = $row['description_items'] ?? [];
                    $description = $row['description'] ?? '';

                    if (is_array($descriptionItems) && count($descriptionItems) > 0) {
                        $description = implode('; ', $descriptionItems);
                    }

                    $pokok = $receipts ? (float) $receipts['pokok'] : 0;
                    $wajib = $receipts ? (float) $receipts['wajib'] : 0;
                    $sukarela = $receipts ? (float) $receipts['sukarela'] : 0;
                    $principal = $receipts ? (float) $receipts['principal'] : 0;
                    $fee = $receipts ? (float) $receipts['fee'] : 0;
                    $other = $receipts ? (float) $receipts['other'] : 0;
                    $receiptsTotal = $receipts ? (float) $row['receipts_total'] : 0;
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">{{ $row['date'] ?? '' }}</td>
                    <td>{{ $description }}</td>
                    <td class="right">{{ number_format($pokok, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($wajib, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($sukarela, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($principal, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($fee, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($other, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($receiptsTotal, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($expensesTotal, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) ($row['balance'] ?? 0), 2, ',', '.') }}</td>
                    <td>{{ $row['note'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

