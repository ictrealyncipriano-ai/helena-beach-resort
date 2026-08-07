<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        // Apply light/dark before first paint to avoid a flash.
        (function () {
            var mode = localStorage.getItem('theme') || 'system';
            var dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in — Helena Beach Resort</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|playfair-display:400,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/admin.css'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { height: 100%; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            height: 100%;
            background: linear-gradient(135deg, var(--color-teal-600) 0%, var(--color-teal-700) 40%, var(--color-teal-800) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow-x: hidden;
        }

        .bg-blob {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }
        .bg-blob div {
            position: absolute;
            border-radius: 50%;
        }
        .bg-blob-1 {
            top: -20%; right: -10%;
            width: 50%; height: 80%;
            background: radial-gradient(ellipse, color-mix(in srgb, var(--color-teal-500) 15%, transparent), transparent 70%);
        }
        .bg-blob-2 {
            bottom: -15%; left: -10%;
            width: 55%; height: 60%;
            background: radial-gradient(ellipse, rgba(245,158,11,0.1), transparent 70%);
        }
        .bg-blob-3 {
            top: 30%; left: 5%;
            width: 25%; height: 40%;
            background: radial-gradient(ellipse, rgba(255,255,255,0.05), transparent 70%);
        }

        .login-card {
            position: relative;
            z-index: 1;
            background: white;
            border-radius: 1.25rem;
            box-shadow: 0 25px 60px rgba(0,0,0,0.15), 0 8px 20px rgba(0,0,0,0.08);
            padding: 2.5rem;
            max-width: 28rem;
            width: 100%;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            margin-bottom: 0.25rem;
        }
        .login-brand img {
            height: 2.5rem;
            width: auto;
            border-radius: 0.5rem;
            flex-shrink: 0;
            box-shadow: 0 2px 8px color-mix(in srgb, var(--color-teal-600) 20%, transparent);
        }
        .login-brand span {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--color-teal-600);
        }

        .login-heading {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-top: 1.25rem;
            margin-bottom: 0.25rem;
        }
        .login-sub {
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 2rem;
        }

        .form-group { margin-bottom: 1.25rem; }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.375rem;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
            border-radius: 0.75rem;
            border: 1.5px solid #e5e7eb;
            background: #f9fafb;
            transition: all 0.2s ease;
        }
        .input-group:focus-within {
            border-color: var(--color-teal-600);
            background: white;
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-teal-600) 10%, transparent);
        }

        .input-group .icon {
            flex-shrink: 0;
            width: 1.25rem;
            height: 1.25rem;
            margin-left: 1rem;
            color: #9ca3af;
            pointer-events: none;
        }
        .input-group:focus-within .icon { color: var(--color-teal-600); }

        .input-group input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: #111827;
            outline: none;
            font-family: inherit;
            min-width: 0;
        }
        .input-group input::placeholder { color: #9ca3af; }

        .input-group .toggle-pw {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            color: #6b7280;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
            margin-right: 0.25rem;
            outline: none;
        }
        .input-group .toggle-pw:hover { background: #f3f4f6; color: #374151; }
        .input-group .toggle-pw:focus-visible { box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-teal-600) 40%, transparent); }
        .input-group .toggle-pw svg { width: 1.25rem; height: 1.25rem; display: block; }

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .form-check input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            border-radius: 0.375rem;
            border: 1.5px solid #d1d5db;
            accent-color: var(--color-teal-600);
            cursor: pointer;
            flex-shrink: 0;
        }

        .btn-submit {
            width: 100%;
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-size: 0.9375rem;
            background: linear-gradient(135deg, var(--color-teal-600), var(--color-teal-500));
            border: none;
            color: white;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px color-mix(in srgb, var(--color-teal-600) 30%, transparent);
            cursor: pointer;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px color-mix(in srgb, var(--color-teal-600) 40%, transparent);
        }
        .btn-submit:active { transform: translateY(0); }
        .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

        .btn-submit .spinner {
            display: none;
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        .btn-submit.loading .spinner { display: block; }
        .btn-submit.loading .btn-text { display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #f3f4f6;
        }
        .login-footer a {
            font-size: 0.75rem;
            color: var(--color-teal-600);
            text-decoration: none;
            font-weight: 500;
        }
        .login-footer a:hover { text-decoration: underline; }
        .login-footer .copyright {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.25rem;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        @media (max-width: 640px) {
            body { padding: 0.75rem; }
            .login-card { padding: 1.5rem; max-width: 100%; margin: 0 0.5rem; }
            .input-group input { font-size: 1rem; }
        }

        .dark body { background: linear-gradient(135deg, #134e4a 0%, #115e59 40%, #134e4a 100%); }
        .dark .login-card { background: #0f172a; box-shadow: 0 25px 60px rgba(0,0,0,0.5); }
        .dark .login-heading { color: #f1f5f9; }
        .dark .login-sub { color: #94a3b8; }
        .dark .form-label { color: #e2e8f0; }
        .dark .input-group { background: #1e293b; border-color: #334155; }
        .dark .input-group:focus-within { background: #1e293b; }
        .dark .input-group input { color: #f1f5f9; }
        .dark .input-group input::placeholder { color: #64748b; }
        .dark .input-group .icon { color: #64748b; }
        .dark .form-check { color: #cbd5e1; }
        .dark .login-footer { border-color: #1e293b; }
        .dark .login-footer .copyright { color: #64748b; }
    </style>
</head>
<body>
    <div class="bg-blob">
        <div class="bg-blob-1"></div>
        <div class="bg-blob-2"></div>
        <div class="bg-blob-3"></div>
    </div>

    <div class="login-card">
        <div class="login-brand">
            <img src="{{ asset('images/logo.jpg') }}" alt="Helena Beach">
            <span>Helena Beach</span>
        </div>

        <h1 class="login-heading">Welcome back</h1>
        <p class="login-sub">Sign in to your account to continue.</p>

        @if ($errors->any())
            <div class="alert" role="alert">
                {{ $errors->first('email') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login') }}" id="login-form">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Email address</label>
                <div class="input-group">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                    </svg>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus autocomplete="email">
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    <button type="button" class="toggle-pw" id="toggle-pw" aria-label="Toggle password visibility">
                        <svg class="eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg class="eye-closed" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" style="display:none">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>

            <label class="form-check">
                <input type="checkbox" name="remember" id="remember">
                Remember me
            </label>

            <button type="submit" class="btn-submit" id="submit-btn">
                <span class="btn-text">Sign in</span>
                <span class="spinner"></span>
            </button>
        </form>

        <div class="login-footer">
            <a href="{{ route('home') }}">← Back to Website</a>
            <div class="copyright">&copy; {{ date('Y') }} Helena Beach Resort</div>
        </div>
    </div>

    <script>
        // Single password toggle
        (function() {
            var btn = document.getElementById('toggle-pw');
            var input = document.getElementById('password');
            var openIcon = btn.querySelector('.eye-open');
            var closedIcon = btn.querySelector('.eye-closed');
            btn.addEventListener('click', function() {
                var revealed = input.type === 'password';
                input.type = revealed ? 'text' : 'password';
                openIcon.style.display = revealed ? 'none' : '';
                closedIcon.style.display = revealed ? '' : 'none';
                btn.setAttribute('aria-label', revealed ? 'Hide password' : 'Show password');
            });
        })();

        // Submit spinner
        (function() {
            var form = document.getElementById('login-form');
            var btn = document.getElementById('submit-btn');
            form.addEventListener('submit', function() {
                btn.classList.add('loading');
                btn.disabled = true;
            });
        })();
    </script>
</body>
</html>
