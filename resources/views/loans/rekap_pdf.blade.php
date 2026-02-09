<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: "Times New Roman", serif; font-size: 11px; color: #111; }
        h1, h2 { text-align: center; margin: 0; }
        h1 { font-size: 14px; letter-spacing: 0.4px; }
        h2 { font-size: 12px; margin-top: 4px; }
        .meta { text-align: center; margin: 8px 0 12px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #111; padding: 4px 6px; }
        th { text-transform: uppercase; font-size: 10px; background: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; background: #f7f7f7; }
    </style>
</head>
<body>
    <h1>KOPERASI BAGI HASIL "ASSALAM"</h1>
    <h2>REKAPITULASI PEMINJAMAN ANGGOTA</h2>
    <div class="meta">Periode {{ $periodLabel }}</div>

    <table>
        <thead>
            <tr>
                <th style="width: 36px;">No</th>
                <th>Nama Anggota</th>
                <th class="text-center">Angsuran Lunas</th>
                <th class="text-center">Sisa Angsuran</th>
                <th class="text-right">Total Pinjaman</th>
                <th class="text-right">Total Terbayar</th>
                <th class="text-right">Sisa Tagihan</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($members as $member)
                @php
                    $isSettled = ($member['remaining'] ?? 0) <= 0.0001;
                    $settledInstallments = (int) ($member['settled'] ?? 0);
                    $totalInstallments = (int) ($member['total_installments'] ?? 0);
                    $remainingInstallments = max($totalInstallments - $settledInstallments, 0);
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $member['name'] }} ({{ $member['member_no'] ?? '-' }})</td>
                    <td class="text-center">{{ $settledInstallments }} kali</td>
                    <td class="text-center">{{ $remainingInstallments }} kali</td>
                    <td class="text-right">Rp {{ number_format($member['loan_amount'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($member['paid'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($member['remaining'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $isSettled ? 'Lunas' : 'Belum Lunas' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Belum ada data peminjaman.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="4" class="text-center">Total</td>
                <td class="text-right">Rp {{ number_format($summaryTotals['loan_amount'] ?? 0, 2, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($summaryTotals['paid'] ?? 0, 2, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($summaryTotals['remaining'] ?? 0, 2, ',', '.') }}</td>
                <td class="text-center">-</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
