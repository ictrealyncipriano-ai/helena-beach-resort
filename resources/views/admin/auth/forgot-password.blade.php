@extends('admin.layouts.auth')

@section('title', 'Forgot Password')
@section('heading', 'Forgot your password?')
@section('sub', "Enter your email and we'll send you a link to reset it.")

@section('alerts')
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
@endsection

@section('form')
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
@endsection

@section('footer-link')
    <a href="{{ route('admin.login') }}">← Back to Sign in</a>
@endsection
