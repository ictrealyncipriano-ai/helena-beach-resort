@extends('admin.layouts.app')

@section('title', $promo->exists ? 'Edit Promo Code' : 'Create Promo Code')
@section('header', $promo->exists ? 'Edit Promo Code' : 'Create Promo Code')

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.promo-codes.index') }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">Promo Codes</a>
        <span>/</span>
        <span class="text-gray-700 font-medium dark:text-slate-200">{{ $promo->exists ? $promo->code : 'New' }}</span>
    </nav>
@endsection

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ $promo->exists ? route('admin.promo-codes.update', $promo) : route('admin.promo-codes.store') }}" class="space-y-6">
        @csrf
        @if($promo->exists) @method('PUT') @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-5 dark:bg-slate-800 dark:border-slate-700">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $promo->code) }}" required maxlength="50" placeholder="e.g. SUMMER10" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20 uppercase">
                    @error('code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Type <span class="text-red-500">*</span></label>
                    <select name="type" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                        <option value="percent" {{ old('type', $promo->type) === 'percent' ? 'selected' : '' }}>Percent (%)</option>
                        <option value="fixed" {{ old('type', $promo->type) === 'fixed' ? 'selected' : '' }}>Fixed amount (₱)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Value <span class="text-red-500">*</span></label>
                    <input type="number" name="value" value="{{ old('value', $promo->value) }}" required step="0.01" min="0.01" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <p class="text-xs text-gray-400 mt-1 dark:text-slate-500">Percent (e.g. 10) or fixed pesos (e.g. 500).</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Min. Spending Amount</label>
                    <input type="number" name="min_amount" value="{{ old('min_amount', $promo->min_amount) }}" step="0.01" min="0" placeholder="Optional" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Valid From</label>
                    <input type="datetime-local" name="valid_from" value="{{ old('valid_from', $promo->valid_from?->format('Y-m-d\TH:i')) }}" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Valid Until</label>
                    <input type="datetime-local" name="valid_until" value="{{ old('valid_until', $promo->valid_until?->format('Y-m-d\TH:i')) }}" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    @error('valid_until') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Usage Limit</label>
                    <input type="number" name="usage_limit" value="{{ old('usage_limit', $promo->usage_limit) }}" min="1" placeholder="Unlimited" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <div class="flex items-end pb-2">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ old('is_active', $promo->exists ? $promo->is_active : true) ? 'checked' : '' }}>
                        <div class="w-9 h-5 bg-gray-200 peer-checked:bg-teal-600 dark:bg-slate-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-teal-600 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                        <span class="ms-2 text-sm font-medium text-gray-700 dark:text-slate-300">Active</span>
                    </label>
                </div>
            </div>
            @if($promo->exists)
                <div class="pt-1 text-xs text-gray-400 dark:text-slate-500">
                    Used {{ $promo->used_count }} times{{ $promo->usage_limit ? ' of '.$promo->usage_limit : '' }}.
                </div>
            @endif
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.promo-codes.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</a>
            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors shadow-sm">
                {{ $promo->exists ? 'Update Promo Code' : 'Create Promo Code' }}
            </button>
        </div>
    </form>
</div>
@endsection