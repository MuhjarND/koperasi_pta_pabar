@php
    $role = $authUser['role'] ?? '';
    $isSuperadmin = $role === 'superadmin';
    $canMemberLoan = in_array($role, ['anggota', 'superadmin']);
    $canSecretary = in_array($role, ['sekretaris', 'superadmin']);
    $canTreasurer = in_array($role, ['bendahara', 'superadmin']);
    $canOfficeTreasurer = in_array($role, ['bendahara_kantor', 'superadmin']);
    $canChairman = in_array($role, ['ketua', 'superadmin']);

    $openFinance = request()->routeIs('saldo.*')
        || request()->routeIs('savings.*')
        || request()->routeIs('deductions.*')
        || request()->routeIs('anggota.loans.*')
        || request()->routeIs('sekretaris.loans.*')
        || request()->routeIs('bendahara.loans.*')
        || request()->routeIs('ketua.loans.*');
    $openManagement = request()->routeIs('users.*') || request()->routeIs('members.*') || request()->routeIs('invites.*');
    $openStore = request()->routeIs('products.*') || request()->routeIs('sales.*');
    $openReports = request()->routeIs('reports.type') || request()->routeIs('reports.*');

    $reportTypes = [
        'shu' => 'SHU',
        'laba-rugi' => 'Laba Rugi',
        'neraca' => 'Neraca',
    ];
@endphp

<div class="nav">
    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard*') ? 'active' : '' }}">
        @include('partials.icon', ['name' => 'dashboard'])
        <span>Dashboard</span>
    </a>

    <details class="nav-group" {{ $openFinance ? 'open' : '' }}>
        <summary class="nav-group-title">
            @include('partials.icon', ['name' => 'wallet'])
            <span>Keuangan</span>
        </summary>
        <div class="nav-sub">
            <a href="{{ route('saldo.index') }}" class="{{ request()->routeIs('saldo.*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'wallet'])
                <span>Saldo Koperasi</span>
            </a>
            <a href="{{ route('savings.index') }}" class="{{ request()->routeIs('savings.*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'coins'])
                <span>Rekap Simpanan</span>
            </a>
            <a href="{{ route('deductions.index') }}" class="{{ request()->routeIs('deductions.*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'clipboard'])
                <span>{{ $canOfficeTreasurer && !$canTreasurer ? 'Verifikasi Pemotongan' : 'Rekap Pemotongan' }}</span>
            </a>
            @if($canMemberLoan || $canSecretary || $canTreasurer || $canChairman)
                <div class="nav-separator"></div>
                @if($canMemberLoan)
                    <a href="{{ route('anggota.loans.index') }}" class="{{ request()->routeIs('anggota.loans.index') ? 'active' : '' }}">
                        @include('partials.icon', ['name' => 'file'])
                        <span>Peminjaman Saya</span>
                    </a>
                    <a href="{{ route('anggota.loans.create') }}" class="{{ request()->routeIs('anggota.loans.create') ? 'active' : '' }}">
                        @include('partials.icon', ['name' => 'plus'])
                        <span>Ajukan Peminjaman</span>
                    </a>
                    <a href="{{ route('anggota.loans.payments') }}" class="{{ request()->routeIs('anggota.loans.payments*') ? 'active' : '' }}">
                        @include('partials.icon', ['name' => 'wallet'])
                        <span>Pembayaran Angsuran</span>
                    </a>
                @endif
                @if($canSecretary)
                    <a href="{{ route('sekretaris.loans.index') }}" class="{{ request()->routeIs('sekretaris.loans.*') ? 'active' : '' }}">
                        @include('partials.icon', ['name' => 'clipboard'])
                        <span>Review Sekretaris</span>
                    </a>
                @endif
                @if($canTreasurer)
                    <a href="{{ route('bendahara.loans.index') }}" class="{{ request()->routeIs('bendahara.loans.*') ? 'active' : '' }}">
                        @include('partials.icon', ['name' => 'check'])
                        <span>Persetujuan Bendahara</span>
                    </a>
                    <a href="{{ route('bendahara.loans.payments') }}" class="{{ request()->routeIs('bendahara.loans.payments*') ? 'active' : '' }}">
                        @include('partials.icon', ['name' => 'users'])
                        <span>Rekap Peminjaman</span>
                    </a>
                @endif
                @if($canChairman)
                    <a href="{{ route('ketua.loans.index') }}" class="{{ request()->routeIs('ketua.loans.*') ? 'active' : '' }}">
                        @include('partials.icon', ['name' => 'star'])
                        <span>Persetujuan Ketua</span>
                    </a>
                @endif
            @endif
        </div>
    </details>

    @if(in_array($role, ['bendahara', 'superadmin']))
        <details class="nav-group" {{ $openReports ? 'open' : '' }}>
            <summary class="nav-group-title">
                @include('partials.icon', ['name' => 'chart'])
                <span>Laporan</span>
            </summary>
            <div class="nav-sub">
                @foreach($reportTypes as $key => $label)
                    <a href="{{ route('reports.type', ['type' => $key]) }}" class="{{ request()->is('laporan/' . $key) ? 'active' : '' }}">
                        @include('partials.icon', ['name' => 'chart'])
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        </details>
    @endif

    @if($isSuperadmin || $canSecretary)
        <details class="nav-group" {{ $openManagement ? 'open' : '' }}>
            <summary class="nav-group-title">
                @include('partials.icon', ['name' => 'users'])
                <span>Manajemen</span>
            </summary>
            <div class="nav-sub">
                @if($isSuperadmin)
                    <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                        @include('partials.icon', ['name' => 'users'])
                        <span>Manajemen User</span>
                    </a>
                @endif
                @if(in_array($role, ['sekretaris', 'superadmin']))
                    <a href="{{ route('members.index') }}" class="{{ request()->routeIs('members.*') ? 'active' : '' }}">
                        @include('partials.icon', ['name' => 'user'])
                        <span>Manajemen Anggota</span>
                    </a>
                @endif
                @if($canSecretary)
                    <a href="{{ route('invites.index') }}" class="{{ request()->routeIs('invites.*') ? 'active' : '' }}">
                        @include('partials.icon', ['name' => 'plus'])
                        <span>Link Pendaftaran</span>
                    </a>
                @endif
            </div>
        </details>
    @endif

    @if($canSecretary)
        <details class="nav-group" {{ $openStore ? 'open' : '' }}>
            <summary class="nav-group-title">
                @include('partials.icon', ['name' => 'box'])
                <span>Koperasi Mart</span>
            </summary>
            <div class="nav-sub">
                <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'box'])
                    <span>Produk Koperasi</span>
                </a>
                <a href="{{ route('sales.index') }}" class="{{ request()->routeIs('sales.*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'cart'])
                    <span>Penjualan</span>
                </a>
            </div>
        </details>
    @endif
</div>
