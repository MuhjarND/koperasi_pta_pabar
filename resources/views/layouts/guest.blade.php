<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title>Koperasi Digital</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_koperasi.png') }}">
    <link rel="stylesheet" href="{{ asset('css/koperasi.css') }}">
</head>
<body>
    <div class="auth-wrapper">
        @yield('content')
    </div>

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
