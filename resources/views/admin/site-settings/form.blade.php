@extends('admin.layouts.app')

@section('title', $setting->exists ? 'Edit Setting' : 'Add Setting')
@section('header', $setting->exists ? 'Edit Setting' : 'Add Setting')

@section('breadcrumb')
    <x-admin.breadcrumb :items="[['label' => 'Site Settings', 'url' => route('admin.site-settings.index')], ['label' => $setting->exists ? $setting->key : 'New']]" />
@endsection

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ $setting->exists ? route('admin.site-settings.update', $setting) : route('admin.site-settings.store') }}" class="space-y-6">
        @csrf
        @if($setting->exists) @method('PUT') @endif

        <x-admin.card :padding="false" class="p-5" :spacing="true">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Key <span class="text-red-500">*</span></label>
                <input type="text" name="key" value="{{ old('key', $setting->key) }}" required maxlength="255" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20 @error('key') border-red-300 dark:border-red-500 @enderror">
                @error('key') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Value</label>
                <textarea name="value" rows="4" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">{{ old('value', $setting->value) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Type <span class="text-red-500">*</span></label>
                <select name="type" required class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <option value="text" {{ old('type', $setting->type) === 'text' ? 'selected' : '' }}>Text</option>
                    <option value="textarea" {{ old('type', $setting->type) === 'textarea' ? 'selected' : '' }}>Textarea</option>
                    <option value="image" {{ old('type', $setting->type) === 'image' ? 'selected' : '' }}>Image</option>
                </select>
            </div>
        </x-admin.card>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.site-settings.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</a>
            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors shadow-sm">
                {{ $setting->exists ? 'Update Setting' : 'Create Setting' }}
            </button>
        </div>
    </form>
</div>
@endsection