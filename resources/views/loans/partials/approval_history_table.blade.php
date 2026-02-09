@php
    $showAction = $showAction ?? false;
    $actionRoute = $actionRoute ?? null;
@endphp
<table class="table">
    <thead>
        <tr>
            <th>Anggota</th>
            <th>Nominal</th>
            <th>Tenor</th>
            <th>Tanggal</th>
            <th>Riwayat Persetujuan</th>
            @if($showAction && $actionRoute)
                <th>Aksi</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse($loans as $loan)
            @php
                $sekStatus = $loan->reviewed_at ? 'Disetujui' : 'Menunggu';
                $sekClass = $loan->reviewed_at ? 'status-pill--success' : '';
                $ketuaStatus = $loan->chairman_approved_at ? 'Disetujui' : 'Menunggu';
                $ketuaClass = $loan->chairman_approved_at ? 'status-pill--success' : '';
                $bendaharaStatus = $loan->treasurer_approved_at ? 'Disetujui' : 'Menunggu';
                $bendaharaClass = $loan->treasurer_approved_at ? 'status-pill--success' : '';
                if (($loan->status ?? '') === 'rejected') {
                    if (!empty($loan->treasurer_approved_at) || !empty($loan->bendahara_name)) {
                        $bendaharaStatus = 'Ditolak';
                        $bendaharaClass = 'status-pill--danger';
                    } elseif (!empty($loan->chairman_approved_at) || !empty($loan->ketua_name)) {
                        $ketuaStatus = 'Ditolak';
                        $ketuaClass = 'status-pill--danger';
                    } elseif (!empty($loan->reviewed_at) || !empty($loan->sekretaris_name)) {
                        $sekStatus = 'Ditolak';
                        $sekClass = 'status-pill--danger';
                    }
                }
                $sekTime = $loan->reviewed_at ? \Carbon\Carbon::parse($loan->reviewed_at)->locale('id')->translatedFormat('d M Y, H:i') : '-';
                $ketuaTime = $loan->chairman_approved_at ? \Carbon\Carbon::parse($loan->chairman_approved_at)->locale('id')->translatedFormat('d M Y, H:i') : '-';
                $bendaharaTime = $loan->treasurer_approved_at ? \Carbon\Carbon::parse($loan->treasurer_approved_at)->locale('id')->translatedFormat('d M Y, H:i') : '-';
            @endphp
            <tr>
                <td>{{ $loan->name }}</td>
                <td>Rp {{ number_format($loan->amount, 2, ',', '.') }}</td>
                <td>{{ $loan->term_months }} bulan</td>
                <td>{{ $loan->created_at }}</td>
                <td>
                    <div class="approval-history">
                        <div class="approval-item">
                            <span class="approval-role">Sekretaris</span>
                            <span class="status-pill {{ $sekClass }}">{{ $sekStatus }}</span>
                            <span class="approval-time">{{ $sekTime }}</span>
                        </div>
                        <div class="approval-item">
                            <span class="approval-role">Ketua</span>
                            <span class="status-pill {{ $ketuaClass }}">{{ $ketuaStatus }}</span>
                            <span class="approval-time">{{ $ketuaTime }}</span>
                        </div>
                        <div class="approval-item">
                            <span class="approval-role">Bendahara</span>
                            <span class="status-pill {{ $bendaharaClass }}">{{ $bendaharaStatus }}</span>
                            <span class="approval-time">{{ $bendaharaTime }}</span>
                        </div>
                    </div>
                </td>
                @if($showAction && $actionRoute)
                    <td>
                        <a class="btn btn-ghost" href="{{ route($actionRoute, $loan->id) }}">Detail</a>
                    </td>
                @endif
            </tr>
        @empty
            <tr>
                <td colspan="{{ $showAction && $actionRoute ? 6 : 5 }}">Belum ada riwayat persetujuan.</td>
            </tr>
        @endforelse
    </tbody>
</table>
