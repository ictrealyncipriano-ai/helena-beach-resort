<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        (function () {
            var mode = localStorage.getItem('theme') || 'system';
            var dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password — Helena Beach Resort</title>
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

        .auth-card {
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

        .auth-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            margin-bottom: 0.25rem;
        }
        .auth-brand img {
            height: 2.5rem;
            width: auto;
            border-radius: 0.5rem;
            flex-shrink: 0;
            box-shadow: 0 2px 8px color-mix(in srgb, var(--color-teal-600) 20%, transparent);
        }
        .auth-brand span {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--color-teal-600);
        }

        .auth-heading {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-top: 1.25rem;
            margin-bottom: 0.25rem;
        }
        .auth-sub {
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

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #f3f4f6;
        }
        .auth-footer a {
            font-size: 0.875rem;
            color: var(--color-teal-600);
            text-decoration: none;
            font-weight: 500;
        }
        .auth-footer a:hover { text-decoration: underline; }
        .auth-footer .copyright {
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
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        @media (max-width: 640px) {
            body { padding: 0.75rem; }
            .auth-card { padding: 1.5rem; max-width: 100%; margin: 0 0.5rem; }
            .input-group input { font-size: 1rem; }
        }

        .dark body { background: linear-gradient(135deg, #134e4a 0%, #115e59 40%, #134e4a 100%); }
        .dark .auth-card { background: #0f172a; box-shadow: 0 25px 60px rgba(0,0,0,0.5); }
        .dark .auth-heading { color: #f1f5f9; }
        .dark .auth-sub { color: #94a3b8; }
        .dark .form-label { color: #e2e8f0; }
        .dark .input-group { background: #1e293b; border-color: #334155; }
        .dark .input-group:focus-within { background: #1e293b; }
        .dark .input-group input { color: #f1f5f9; }
        .dark .input-group input::placeholder { color: #64748b; }
        .dark .input-group .icon { color: #64748b; }
        .dark .auth-footer { border-color: #1e293b; }
        .dark .auth-footer .copyright { color: #64748b; }
    </style>
</head>
<body>
    <div class="bg-blob">
        <div class="bg-blob-1"></div>
        <div class="bg-blob-2"></div>
        <div class="bg-blob-3"></div>
    </div>

    <div class="auth-card">
        <div class="auth-brand">
            <img src="{{ asset('images/logo.jpg') }}" alt="Helena Beach">
            <span>Helena Beach</span>
        </div>

        <h1 class="auth-heading">Forgot your password?</h1>
        <p class="auth-sub">Enter your email and we'll send you a link to reset it.</p>

        @if (session('status'))
            <div class="alert alert-success" role="alert">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert" role="alert">
                {{ $errors->first('email') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.password.email') }}" id="forgot-form">
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

            <button type="submit" class="btn-submit" id="submit-btn">
                <span class="btn-text">Send Reset Link</span>
                <span class="spinner"></span>
            </button>
        </form>

        <div class="auth-footer">
            <a href="{{ route('admin.login') }}">← Back to Sign in</a>
            <div class="copyright">&copy; {{ date('Y') }} Helena Beach Resort</div>
        </div>
    </div>

    <script>
        (function () {
            var form = document.getElementById('forgot-form');
            var btn = document.getElementById('submit-btn');
            form.addEventListener('submit', function () {
                btn.classList.add('loading');
                btn.disabled = true;
            });
        })();
    </script>
</body>
</html>
