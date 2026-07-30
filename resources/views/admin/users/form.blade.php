@extends('admin.layouts.app')

@section('title', $user->exists ? 'Edit User' : 'Create User')
@section('header', $user->exists ? 'Edit User' : 'Create User')

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-600 transition-colors">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.users.index') }}" class="hover:text-teal-600 transition-colors">Users</a>
        <span>/</span>
        <span class="text-gray-700 font-medium">{{ $user->exists ? $user->name : 'New' }}</span>
    </nav>
@endsection

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="space-y-6">
        @csrf
        @if($user->exists)
            @method('PUT')
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Account Information</h2>
                <p class="text-xs text-gray-500 mt-0.5">Basic details for the user account.</p>
            </div>
            <div class="p-5">
                <div x-data="{ isEditing: {{ $user->exists ? 'true' : 'false' }}, form: { name: '{{ $user->name }}', email: '{{ $user->email }}', password: '', role: '{{ $user->role ?? 'admin' }}' } }">
                    @include('admin.users._form')
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors shadow-sm">
                {{ $user->exists ? 'Update User' : 'Create User' }}
            </button>
        </div>
    </form>
</div>
@endsection
