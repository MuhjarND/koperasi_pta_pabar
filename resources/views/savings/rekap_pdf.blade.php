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
    <h2>REKAPITULASI SIMPANAN ANGGOTA</h2>
    <div class="meta">Periode {{ $periodLabel }}</div>

    <table>
        <thead>
            <tr>
                <th style="width: 36px;">No</th>
                <th>Nama Anggota</th>
                @foreach($types as $label)
                    <th class="text-right">{{ $label }}</th>
                @endforeach
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($memberSummaries as $member)
                @php
                    $typeTotals = array_fill_keys(array_keys($types), 0);
                    foreach ($member['months'] as $month) {
                        foreach ($types as $key => $label) {
                            $typeTotals[$key] += (float) (($month['movement_types'] ?? $month['types'])[$key] ?? 0);
                        }
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $member['name'] }} ({{ $member['member_no'] ?? '-' }})</td>
                    @foreach($types as $key => $label)
                        <td class="text-right">Rp {{ number_format($typeTotals[$key] ?? 0, 2, ',', '.') }}</td>
                    @endforeach
                    <td class="text-right">Rp {{ number_format($member['total_amount'] ?? 0, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 3 + count($types) }}" class="text-center">Belum ada data simpanan.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2" class="text-center">Total</td>
                @foreach($types as $key => $label)
                    <td class="text-right">Rp {{ number_format($summaryTotals['types'][$key] ?? 0, 2, ',', '.') }}</td>
                @endforeach
                <td class="text-right">Rp {{ number_format($summaryTotals['total'] ?? 0, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
