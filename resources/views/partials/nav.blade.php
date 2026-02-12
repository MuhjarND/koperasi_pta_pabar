@php
    $role = $authUser['role'] ?? '';
    $isSuperadmin = $role === 'superadmin';
    $canMemberLoan = in_array($role, ['anggota', 'sekretaris', 'bendahara', 'bendahara_kantor', 'ketua', 'superadmin']);
    $canSecretary = in_array($role, ['sekretaris', 'superadmin']);
    $canTreasurer = in_array($role, ['bendahara', 'superadmin']);
    $canOfficeTreasurer = in_array($role, ['bendahara_kantor', 'superadmin']);
    $canChairman = in_array($role, ['ketua', 'superadmin']);

    $openFinance = request()->routeIs('saldo.*')
        || request()->routeIs('savings.*')
        || request()->routeIs('deductions.*')
        || request()->routeIs('anggota.keuangan')
        || request()->routeIs('anggota.loans.*')
        || request()->routeIs('sekretaris.loans.*')
        || request()->routeIs('bendahara.loans.*')
        || request()->routeIs('ketua.loans.*');
    $openMemberLoans = request()->routeIs('anggota.loans.*') || request()->routeIs('anggota.keuangan');
    $openManagement = request()->routeIs('users.*') || request()->routeIs('members.*') || request()->routeIs('invites.*');
    $openStore = request()->routeIs('products.*') || request()->routeIs('sales.*') || request()->routeIs('mart.balance.*');
    $openReports = request()->routeIs('reports.type') || request()->routeIs('reports.*');

    $pendingSecretaryLoans = $canSecretary ? DB::table('loans')->where('status', 'submitted')->count() : 0;
    $pendingCashEntries = $canSecretary ? DB::table('cash_entries')->where('status', 'pending')->count() : 0;
    $pendingSecretarySettlements = $canSecretary
        ? DB::table('loan_installment_payments as p')
            ->join('users as creator', 'p.created_by', '=', 'creator.id')
            ->where('p.is_settlement', 1)
            ->where('p.status', 'pending')
            ->where('creator.role', 'bendahara')
            ->count()
        : 0;
    $pendingTreasurerLoans = $canTreasurer ? DB::table('loans')->where('status', 'reviewed')->count() : 0;
    $pendingChairmanLoans = $canChairman ? DB::table('loans')->where('status', 'approved_treasurer')->count() : 0;
    $pendingDeductions = $canOfficeTreasurer ? DB::table('deduction_logs')->where('status', 'pending')->count() : 0;
    $pendingSettlements = $canTreasurer
        ? DB::table('loan_installment_payments as p')
            ->leftJoin('users as creator', 'p.created_by', '=', 'creator.id')
            ->where('p.is_settlement', 1)
            ->where('p.status', 'pending')
            ->where(function ($query) {
                $query->whereNull('creator.role')
                    ->orWhere('creator.role', '!=', 'bendahara');
            })
            ->count()
        : 0;
    $pendingDisbursements = $canTreasurer
        ? DB::table('loans')->where('status', 'approved_chairman')->whereNull('transfer_evidence_path')->count()
        : 0;

    $hasApprovalMenu = $canSecretary || $canTreasurer || $canChairman || $canOfficeTreasurer;

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


    @if($role === 'anggota')
        <a href="{{ route('anggota.loans.create') }}" class="{{ request()->routeIs('anggota.loans.create') ? 'active' : '' }}">
            @include('partials.icon', ['name' => 'plus'])
            <span>Ajukan Peminjaman</span>
        </a>
        <a href="{{ route('anggota.loans.index') }}" class="{{ request()->routeIs('anggota.loans.index') ? 'active' : '' }}">
            @include('partials.icon', ['name' => 'file'])
            <span>Pinjaman Saya</span>
        </a>
        <a href="{{ route('anggota.loans.payments') }}" class="{{ request()->routeIs('anggota.loans.payments*') ? 'active' : '' }}">
            @include('partials.icon', ['name' => 'wallet'])
            <span>Pembayaran Angsuran</span>
        </a>
        <a href="{{ route('anggota.keuangan') }}" class="{{ request()->routeIs('anggota.keuangan') ? 'active' : '' }}">
            @include('partials.icon', ['name' => 'coins'])
            <span>Rekap Simpanan & Pemotongan</span>
        </a>
    @endif

    @if($hasApprovalMenu)
        <div class="nav-section-title">Persetujuan</div>
        @if($canSecretary)
            <a href="{{ route('sekretaris.loans.index') }}" class="nav-approval {{ $pendingSecretaryLoans > 0 ? 'nav-state--pending' : 'nav-state--ok' }} {{ request()->routeIs('sekretaris.loans.*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'clipboard'])
                <span>Review Sekretaris</span>
                @if($pendingSecretaryLoans > 0)
                    <span class="nav-badge">{{ $pendingSecretaryLoans }}</span>
                @endif
            </a>
            <a href="{{ route('sekretaris.loans.settlements') }}" class="nav-approval {{ $pendingSecretarySettlements > 0 ? 'nav-state--pending' : 'nav-state--ok' }} {{ request()->routeIs('sekretaris.loans.settlements*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'check'])
                <span>Verifikasi Pelunasan</span>
                @if($pendingSecretarySettlements > 0)
                    <span class="nav-badge">{{ $pendingSecretarySettlements }}</span>
                @endif
            </a>
            <a href="{{ route('saldo.index') }}" class="nav-approval {{ $pendingCashEntries > 0 ? 'nav-state--pending' : 'nav-state--ok' }} {{ request()->routeIs('saldo.*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'wallet'])
                <span>Verifikasi Kas</span>
                @if($pendingCashEntries > 0)
                    <span class="nav-badge">{{ $pendingCashEntries }}</span>
                @endif
            </a>
        @endif
        @if($canTreasurer)
            <a href="{{ route('bendahara.loans.index') }}" class="nav-approval {{ $pendingTreasurerLoans > 0 ? 'nav-state--pending' : 'nav-state--ok' }} {{ request()->routeIs('bendahara.loans.index') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'check'])
                <span>Persetujuan Bendahara</span>
                @if($pendingTreasurerLoans > 0)
                    <span class="nav-badge">{{ $pendingTreasurerLoans }}</span>
                @endif
            </a>
            <a href="{{ route('bendahara.loans.disbursement') }}" class="nav-approval {{ $pendingDisbursements > 0 ? 'nav-state--pending' : 'nav-state--ok' }} {{ request()->routeIs('bendahara.loans.disbursement*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'wallet'])
                <span>Pencairan Pinjaman</span>
                @if($pendingDisbursements > 0)
                    <span class="nav-badge">{{ $pendingDisbursements }}</span>
                @endif
            </a>
            <a href="{{ route('bendahara.loans.payments') }}" class="nav-approval {{ $pendingSettlements > 0 ? 'nav-state--pending' : 'nav-state--ok' }} {{ request()->routeIs('bendahara.loans.payments*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'users'])
                <span>Validasi Pelunasan</span>
                @if($pendingSettlements > 0)
                    <span class="nav-badge">{{ $pendingSettlements }}</span>
                @endif
            </a>
        @endif
        @if($canChairman)
            <a href="{{ route('ketua.loans.index') }}" class="nav-approval {{ $pendingChairmanLoans > 0 ? 'nav-state--pending' : 'nav-state--ok' }} {{ request()->routeIs('ketua.loans.*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'star'])
                <span>Persetujuan Ketua</span>
                @if($pendingChairmanLoans > 0)
                    <span class="nav-badge">{{ $pendingChairmanLoans }}</span>
                @endif
            </a>
        @endif
        @if($canOfficeTreasurer)
            <a href="{{ route('deductions.index') }}" class="nav-approval {{ $pendingDeductions > 0 ? 'nav-state--pending' : 'nav-state--ok' }} {{ request()->routeIs('deductions.*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'clipboard'])
                <span>Verifikasi Pemotongan</span>
                @if($pendingDeductions > 0)
                    <span class="nav-badge">{{ $pendingDeductions }}</span>
                @endif
            </a>
        @endif
    @endif

    @if($role !== 'anggota' && $canMemberLoan)
        <details class="nav-group" {{ $openMemberLoans ? 'open' : '' }}>
            <summary class="nav-group-title">
                @include('partials.icon', ['name' => 'file'])
                <span>Peminjaman</span>
            </summary>
            <div class="nav-sub">
                <a href="{{ route('anggota.loans.create') }}" class="{{ request()->routeIs('anggota.loans.create') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'plus'])
                    <span>Ajukan Peminjaman</span>
                </a>
                <a href="{{ route('anggota.loans.index') }}" class="{{ request()->routeIs('anggota.loans.index') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'file'])
                    <span>Pinjaman Saya</span>
                </a>
                <a href="{{ route('anggota.loans.payments') }}" class="{{ request()->routeIs('anggota.loans.payments*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'wallet'])
                    <span>Pembayaran Angsuran</span>
                </a>
                <a href="{{ route('anggota.keuangan') }}" class="{{ request()->routeIs('anggota.keuangan') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'coins'])
                    <span>Rekap Simpanan & Pemotongan</span>
                </a>
            </div>
        </details>
    @endif

    @if($role !== 'anggota')
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
                @if(!($canOfficeTreasurer && !$canTreasurer))
                    <a href="{{ route('deductions.index') }}" class="{{ request()->routeIs('deductions.*') ? 'active' : '' }}">
                        @include('partials.icon', ['name' => 'clipboard'])
                        <span>Rekap Pemotongan</span>
                    </a>
                @endif
                @if($canTreasurer)
                    <div class="nav-separator"></div>
                    <a href="{{ route('bendahara.loans.payments') }}" class="{{ request()->routeIs('bendahara.loans.payments*') ? 'active' : '' }}">
                        @include('partials.icon', ['name' => 'users'])
                        <span>Rekap Peminjaman</span>
                    </a>
                @endif
            </div>
        </details>
    @endif

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
                <a href="{{ route('mart.balance.index') }}" class="{{ request()->routeIs('mart.balance.*') ? 'active' : '' }}">
                    @include('partials.icon', ['name' => 'wallet'])
                    <span>Saldo Koperasi Mart</span>
                </a>
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
