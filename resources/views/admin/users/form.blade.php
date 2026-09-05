@extends('admin.layouts.app')

@section('title', $user->exists ? 'Edit User' : 'Create User')
@section('header', $user->exists ? 'Edit User' : 'Create User')

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.users.index') }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">Users</a>
        <span>/</span>
        <span class="text-gray-700 font-medium dark:text-slate-200">{{ $user->exists ? $user->name : 'New' }}</span>
    </nav>
@endsection

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="space-y-6">
        @csrf
        @if($user->exists)
            @method('PUT')
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden dark:bg-slate-800 dark:border-slate-700">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Account Information</h2>
                <p class="text-xs text-gray-500 mt-0.5 dark:text-slate-400">Basic details for the user account.</p>
            </div>
            <div class="p-5">
                {{-- User-controlled values are embedded as a @js() JSON payload
                     (hex-escaped: \u0027 \u003C etc.) so they can never execute
                     as script after HTML-attribute decoding. --}}
                <div x-data="{
                    isEditing: @js($user->exists),
                    form: @js([
                        'name' => old('name', $user->name ?? ''),
                        'email' => old('email', $user->email ?? ''),
                        'password' => '',
                        'role' => old('role', $user->role ?? 'admin'),
                    ])
                }">
                    @include('admin.users._form')
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</a>
            <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors shadow-sm">
                {{ $user->exists ? 'Update User' : 'Create User' }}
            </button>
        </div>
    </form>
</div>
@endsection
