<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#009848">
    <title>Koperasi Digital</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_koperasi.png') }}">
    <link rel="stylesheet" href="{{ asset('css/koperasi.css') }}?v={{ file_exists(public_path('css/koperasi.css')) ? filemtime(public_path('css/koperasi.css')) : time() }}">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="sidebar-card">
                <div class="sidebar-head">
                    <div class="brand">
                        <img src="{{ asset('logo_koperasi.png') }}" alt="Logo Koperasi" class="brand-logo">
                        <div class="brand-text">
                            <span>Koperasi</span><span>Digital</span>
                        </div>
                    </div>
                    <button class="icon-button sidebar-icon-toggle" type="button" id="sidebar-toggle" aria-pressed="false" title="Sembunyikan/Tampilkan Menu">
                        @include('partials.icon', ['name' => 'menu'])
                    </button>
                </div>
                @include('partials.nav')
                <div class="sidebar-footer">
                    <div>{{ $authUser['name'] ?? 'User' }}</div>
                    <div>{{ config('koperasi.roles.' . ($authUser['role'] ?? '')) }}</div>
                </div>
            </div>
        </aside>
        <main class="main">
            @php
                $twoFactorActive = !empty($authUser['two_factor_enabled']);
                $role = $authUser['role'] ?? '';
                $mobileApprovalRoles = ['sekretaris', 'bendahara', 'bendahara_kantor', 'ketua'];
                $hasMobileApprovalRole = in_array($role, $mobileApprovalRoles, true);
                $mobilePendingCount = 0;

                if ($role === 'sekretaris') {
                    $mobilePendingCount += (int) \Illuminate\Support\Facades\DB::table('loans')->where('status', 'submitted')->count();
                    $mobilePendingCount += (int) \Illuminate\Support\Facades\DB::table('cash_entries')->where('status', 'pending')->count();
                    $mobilePendingCount += (int) \Illuminate\Support\Facades\DB::table('loan_installment_payments as p')
                        ->join('users as creator', 'p.created_by', '=', 'creator.id')
                        ->where('p.is_settlement', 1)
                        ->where('p.status', 'pending')
                        ->where('creator.role', 'bendahara')
                        ->count();
                } elseif ($role === 'bendahara') {
                    $mobilePendingCount += (int) \Illuminate\Support\Facades\DB::table('loans')->where('status', 'reviewed')->count();
                    $mobilePendingCount += (int) \Illuminate\Support\Facades\DB::table('loan_installment_payments as p')
                        ->leftJoin('users as creator', 'p.created_by', '=', 'creator.id')
                        ->where('p.is_settlement', 1)
                        ->where('p.status', 'pending')
                        ->where(function ($query) {
                            $query->whereNull('creator.role')
                                ->orWhere('creator.role', '!=', 'bendahara');
                        })
                        ->count();
                    $mobilePendingCount += (int) \Illuminate\Support\Facades\DB::table('loans')
                        ->where('status', 'approved_chairman')
                        ->whereNull('transfered_at')
                        ->whereNull('transfer_evidence_path')
                        ->count();
                } elseif ($role === 'bendahara_kantor') {
                    $mobilePendingCount += (int) \Illuminate\Support\Facades\DB::table('deduction_logs')->where('status', 'pending')->count();
                } elseif ($role === 'ketua') {
                    $mobilePendingCount += (int) \Illuminate\Support\Facades\DB::table('loans')->where('status', 'approved_treasurer')->count();
                }
            @endphp
            <div class="top-user-strip">
                <div class="top-user-meta">
                    <div class="top-user-role">{{ config('koperasi.roles.' . ($authUser['role'] ?? '')) }}</div>
                    <div class="top-user-name">{{ $authUser['name'] ?? 'User' }}</div>
                </div>
                <div class="top-user-actions">
                    <a class="icon-button icon-button--qr {{ $twoFactorActive ? 'icon-button--ok' : 'icon-button--alert' }}" href="{{ route('authenticator.setup') }}" title="Authenticator">
                        @include('partials.icon', ['name' => 'qr'])
                    </a>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-ghost btn-logout" type="submit">Keluar</button>
                    </form>
                </div>
            </div>
            <div class="page-header page-header--friendly">
                <div class="page-title">
                    <div class="page-kicker">Selamat datang</div>
                    <h1>@yield('title')</h1>
                    <p>@yield('subtitle')</p>
                </div>
            </div>

            @if(session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert danger">
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <div class="mobile-tabs">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard*') ? 'active' : '' }}">
            @include('partials.icon', ['name' => 'dashboard'])
            <span>Beranda</span>
        </a>
        @if(($authUser['role'] ?? '') === 'anggota')
            <a href="{{ route('anggota.loans.create') }}" class="mobile-tab-plus {{ request()->routeIs('anggota.loans.create') ? 'active' : '' }}" aria-label="Pinjam">
                <span class="mobile-tab-plus-symbol" aria-hidden="true">+</span>
                <span class="mobile-tab-plus-label">Pinjam</span>
            </a>
        @else
            <a href="{{ route('saldo.index') }}" class="{{ request()->routeIs('saldo.*') ? 'active' : '' }}">
                @include('partials.icon', ['name' => 'wallet'])
                <span>Kas</span>
            </a>
        @endif
        <button
            type="button"
            class="mobile-tab-button {{ $hasMobileApprovalRole ? 'has-approval-role' : '' }} {{ $hasMobileApprovalRole ? ($mobilePendingCount > 0 ? 'is-pending' : 'is-ok') : '' }}"
            id="mobile-menu-toggle"
        >
            @include('partials.icon', ['name' => 'clipboard'])
            <span>Lainnya</span>
            @if($hasMobileApprovalRole && $mobilePendingCount > 0)
                <span class="mobile-tab-badge">{{ $mobilePendingCount }}</span>
            @endif
        </button>
    </div>

    <div class="mobile-menu" id="mobile-menu">
        <div class="mobile-menu-card">
            <div class="mobile-menu-header">
                <div class="brand">
                    <img src="{{ asset('logo_koperasi.png') }}" alt="Logo Koperasi" class="brand-logo">
                    <div class="brand-text">
                        <span>Koperasi</span><span>Digital</span>
                    </div>
                </div>
                <button type="button" class="btn btn-ghost" id="mobile-menu-close">Tutup</button>
            </div>
            @include('partials.nav')
        </div>
    </div>

    <script>
        (function () {
            var menu = document.getElementById('mobile-menu');
            var openButton = document.getElementById('mobile-menu-toggle');
            var closeButton = document.getElementById('mobile-menu-close');

            if (!menu || !openButton) {
                return;
            }

            function openMenu() {
                menu.classList.add('open');
                openButton.classList.add('active');
            }

            function closeMenu() {
                menu.classList.remove('open');
                openButton.classList.remove('active');
            }

            openButton.addEventListener('click', openMenu);

            if (closeButton) {
                closeButton.addEventListener('click', closeMenu);
            }

            menu.addEventListener('click', function (event) {
                if (event.target === menu) {
                    closeMenu();
                }
            });
        })();
    </script>

    <script>
        (function () {
            var toggleButton = document.getElementById('sidebar-toggle');
            if (!toggleButton) {
                return;
            }

            var storageKey = 'koperasiSidebarCollapsed';

            function updateButtonState() {
                var collapsed = document.body.classList.contains('sidebar-collapsed');
                toggleButton.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
                toggleButton.setAttribute('title', collapsed ? 'Tampilkan Menu' : 'Sembunyikan Menu');
            }

            if (window.localStorage && localStorage.getItem(storageKey) === '1') {
                document.body.classList.add('sidebar-collapsed');
            }
            updateButtonState();

            toggleButton.addEventListener('click', function () {
                document.body.classList.toggle('sidebar-collapsed');
                if (window.localStorage) {
                    localStorage.setItem(storageKey, document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
                }
                updateButtonState();
            });
        })();
    </script>

    <script>
        (function () {
            var tables = document.querySelectorAll('table.table');

            tables.forEach(function (table) {
                var headerCells = table.querySelectorAll('thead th');
                if (!headerCells.length) {
                    return;
                }

                var headers = Array.prototype.map.call(headerCells, function (cell) {
                    return (cell.textContent || '').trim();
                });

                table.querySelectorAll('tbody tr').forEach(function (row) {
                    row.querySelectorAll('td').forEach(function (cell, index) {
                        if (cell.hasAttribute('data-label') || cell.hasAttribute('colspan')) {
                            return;
                        }
                        cell.setAttribute('data-label', headers[index] || '');
                    });
                });
            });
        })();
    </script>

    <script>
        (function () {
            function shouldFormat(input) {
                if (input.dataset.currency === 'false') {
                    return false;
                }
                var name = (input.name || '').toLowerCase();
                return /(amount|nominal|harga|price|total|fee|jasa|pokok|wajib|sukarela)/.test(name);
            }

            function formatNumber(value) {
                var digits = value.replace(/[^\d]/g, '');
                if (!digits) {
                    return '';
                }
                return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function attachCurrency(input) {
                if (!shouldFormat(input)) {
                    return;
                }

                input.dataset.currency = 'true';
                if (input.type === 'number') {
                    input.type = 'text';
                }
                input.setAttribute('inputmode', 'numeric');
                input.setAttribute('autocomplete', 'off');
                input.value = formatNumber(input.value);

                input.addEventListener('input', function () {
                    var formatted = formatNumber(input.value);
                    input.value = formatted;
                });
            }

            document.querySelectorAll('input').forEach(attachCurrency);

            document.querySelectorAll('form').forEach(function (form) {
                form.addEventListener('submit', function () {
                    form.querySelectorAll('input').forEach(function (input) {
                        if (input.dataset.currency === 'true') {
                            input.value = input.value.replace(/[^\d]/g, '');
                        }
                    });
                });
            });
        })();
    </script>

    <script>
        (function () {
            var toggles = document.querySelectorAll('.password-toggle');
            toggles.forEach(function (button) {
                var wrapper = button.closest('.password-field');
                if (!wrapper) {
                    return;
                }
                var input = wrapper.querySelector('input');
                if (!input) {
                    return;
                }

                button.addEventListener('click', function () {
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    button.classList.toggle('active', show);
                    button.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
                });
            });
        })();
    </script>
</body>
</html>
