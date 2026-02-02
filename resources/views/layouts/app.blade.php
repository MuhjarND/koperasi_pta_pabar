<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Koperasi Digital</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_koperasi.png') }}">
    <link rel="stylesheet" href="{{ asset('css/koperasi.css') }}">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="sidebar-card">
                <div class="brand">
                    <img src="{{ asset('logo_koperasi.png') }}" alt="Logo Koperasi" class="brand-logo">
                    <div class="brand-text">
                        <span>Koperasi</span><span>Digital</span>
                    </div>
                </div>
                @include('partials.nav')
                <div class="sidebar-footer">
                    <div>{{ $authUser['name'] ?? 'User' }}</div>
                    <div>{{ config('koperasi.roles.' . ($authUser['role'] ?? '')) }}</div>
                </div>
            </div>
        </aside>
        <main class="main">
            <div class="page-header">
                <div class="page-title">
                    <h1>@yield('title')</h1>
                    <p>@yield('subtitle')</p>
                </div>
                <div class="user-pill">
                    <div>
                        <div class="user-role">{{ config('koperasi.roles.' . ($authUser['role'] ?? '')) }}</div>
                        <div>{{ $authUser['name'] ?? 'User' }}</div>
                    </div>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-ghost" type="submit">Keluar</button>
                    </form>
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
            <span>Dashboard</span>
        </a>
        <a href="{{ route('saldo.index') }}" class="{{ request()->routeIs('saldo.*') ? 'active' : '' }}">
            @include('partials.icon', ['name' => 'wallet'])
            <span>Saldo</span>
        </a>
        <button type="button" class="mobile-tab-button" id="mobile-menu-toggle">
            @include('partials.icon', ['name' => 'clipboard'])
            <span>Menu</span>
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
            }

            function closeMenu() {
                menu.classList.remove('open');
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
</body>
</html>
