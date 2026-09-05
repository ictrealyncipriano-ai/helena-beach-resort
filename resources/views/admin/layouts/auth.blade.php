<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.partials.theme-init')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <title>@yield('title', 'Sign in') — Helena Beach Resort</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|playfair-display:400,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/admin.css', 'resources/css/admin-auth.css'])
</head>
<body>
    <div class="bg-blob" aria-hidden="true">
        <div class="bg-blob-1"></div>
        <div class="bg-blob-2"></div>
        <div class="bg-blob-3"></div>
    </div>

    <div class="auth-card">
        <div class="auth-brand">
            <img src="{{ asset('images/logo.jpg') }}" alt="Helena Beach">
            <span>Helena Beach</span>
        </div>

        <h1 class="auth-heading">@yield('heading')</h1>
        <p class="auth-sub">@yield('sub')</p>

        @yield('alerts')

        @yield('form')

        <div class="auth-footer">
            @yield('footer-link')
            <div class="copyright">&copy; {{ date('Y') }} Helena Beach Resort</div>
        </div>
    </div>

    <script>
        // Generic password toggles (data-target) + submit spinner for all auth forms.
        (function () {
            document.querySelectorAll('.toggle-pw').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var targetId = btn.getAttribute('data-target') || btn.id.replace('toggle-', '');
                    var input = document.getElementById(targetId === 'pw' ? 'password' : targetId);
                    if (!input) return;
                    var revealed = input.type === 'password';
                    input.type = revealed ? 'text' : 'password';
                    var openIcon = btn.querySelector('.eye-open');
                    var closedIcon = btn.querySelector('.eye-closed');
                    if (openIcon) openIcon.style.display = revealed ? 'none' : '';
                    if (closedIcon) closedIcon.style.display = revealed ? '' : 'none';
                    btn.setAttribute('aria-label', revealed ? 'Hide password' : 'Show password');
                });
            });

            document.querySelectorAll('form').forEach(function (form) {
                form.addEventListener('submit', function () {
                    var btn = form.querySelector('.btn-submit');
                    if (btn) {
                        btn.classList.add('loading');
                        btn.disabled = true;
                    }
                });
            });
        })();
    </script>
</body>
</html>
