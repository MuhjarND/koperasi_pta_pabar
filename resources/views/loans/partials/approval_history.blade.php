@php
    $steps = [
        [
            'label' => 'Sekretaris',
            'name' => $loan->sekretaris_name ?? '-',
            'note' => $loan->sekretaris_note ?? null,
            'time' => $loan->reviewed_at ?? null,
            'rejected' => ($loan->status ?? '') === 'rejected' && !empty($loan->sekretaris_id),
            'approved' => !empty($loan->reviewed_at),
        ],
        [
            'label' => 'Ketua',
            'name' => $loan->ketua_name ?? '-',
            'note' => $loan->ketua_note ?? null,
            'time' => $loan->chairman_approved_at ?? null,
            'rejected' => ($loan->status ?? '') === 'rejected' && !empty($loan->ketua_id),
            'approved' => !empty($loan->chairman_approved_at),
        ],
        [
            'label' => 'Bendahara',
            'name' => $loan->bendahara_name ?? '-',
            'note' => $loan->bendahara_note ?? null,
            'time' => $loan->treasurer_approved_at ?? null,
            'rejected' => ($loan->status ?? '') === 'rejected' && !empty($loan->bendahara_id),
            'approved' => !empty($loan->treasurer_approved_at),
        ],
    ];
@endphp

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <div class="card-icon">@include('partials.icon', ['name' => 'history'])</div>
            <h3>Riwayat Persetujuan</h3>
        </div>
    </div>
    <table class="table table-compact">
        <thead>
            <tr>
                <th>Peran</th>
                <th>Nama</th>
                <th>Status</th>
                <th>Waktu</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($steps as $step)
                @php
                    $statusLabel = $step['approved'] ? 'Disetujui' : ($step['rejected'] ? 'Ditolak' : 'Menunggu');
                    $badgeClass = $step['approved']
                        ? 'status-pill--success'
                        : ($step['rejected'] ? 'status-pill--danger' : '');
                    $timeLabel = $step['time']
                        ? \Carbon\Carbon::parse($step['time'])->locale('id')->translatedFormat('d F Y, H:i')
                        : '-';
                @endphp
                <tr>
                    <td>{{ $step['label'] }}</td>
                    <td>{{ $step['name'] }}</td>
                    <td>
                        <span class="status-pill {{ $badgeClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td>{{ $timeLabel }}</td>
                    <td>{{ $step['note'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
