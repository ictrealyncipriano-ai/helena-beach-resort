@extends('admin.layouts.app')

@section('title', $setting->exists ? 'Edit Setting' : 'Add Setting')
@section('header', $setting->exists ? 'Edit Setting' : 'Add Setting')

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-600 transition-colors">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.site-settings.index') }}" class="hover:text-teal-600 transition-colors">Site Settings</a>
        <span>/</span>
        <span class="text-gray-700 font-medium">{{ $setting->exists ? $setting->key : 'New' }}</span>
    </nav>
@endsection

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ $setting->exists ? route('admin.site-settings.update', $setting) : route('admin.site-settings.store') }}" class="space-y-6">
        @csrf
        @if($setting->exists) @method('PUT') @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Key <span class="text-red-500">*</span></label>
                <input type="text" name="key" value="{{ old('key', $setting->key) }}" required maxlength="255" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 @error('key') border-red-300 @enderror">
                @error('key') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Value</label>
                <textarea name="value" rows="4" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400">{{ old('value', $setting->value) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                <select name="type" required class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 bg-white">
                    <option value="text" {{ old('type', $setting->type) === 'text' ? 'selected' : '' }}>Text</option>
                    <option value="textarea" {{ old('type', $setting->type) === 'textarea' ? 'selected' : '' }}>Textarea</option>
                    <option value="image" {{ old('type', $setting->type) === 'image' ? 'selected' : '' }}>Image</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.site-settings.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors shadow-sm">
                {{ $setting->exists ? 'Update Setting' : 'Create Setting' }}
            </button>
        </div>
    </form>
</div>
@endsection