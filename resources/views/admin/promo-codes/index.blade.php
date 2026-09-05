@extends('admin.layouts.app')

@section('title', 'Promo Codes')
@section('header', 'Promo Codes')
@section('description', 'Manage discount codes for bookings')

@section('breadcrumb')
    <x-admin.breadcrumb :items="[['label' => 'Promo Codes']]" />
@endsection

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 dark:border-slate-700">
            <form method="GET" class="flex flex-wrap gap-3 flex-1">
                <div class="relative flex-1 min-w-[180px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search codes..." class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <select name="is_active" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <option value="">All</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors">Filter</button>
                @if(request()->anyFilled(['search', 'is_active']))
                    <a href="{{ route('admin.promo-codes.index') }}" class="px-3 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Clear</a>
                @endif
            </form>
            <a href="{{ route('admin.promo-codes.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-lg hover:bg-teal-800 transition-colors shadow-sm whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Promo Code
            </a>
        </div>

        @if($promoCodes->isEmpty())
            <x-admin.empty-state
                title="No promo codes"
                message="Create promo codes to offer booking discounts."
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider dark:border-slate-700 dark:text-slate-400">
                            <th class="text-left px-5 py-3 font-medium">Code</th>
                            <th class="text-left px-5 py-3 font-medium">Discount</th>
                            <th class="text-left px-5 py-3 font-medium hidden md:table-cell">Min. Spend</th>
                            <th class="text-left px-5 py-3 font-medium hidden lg:table-cell">Valid</th>
                            <th class="text-center px-5 py-3 font-medium">Usage</th>
                            <th class="text-center px-5 py-3 font-medium">Active</th>
                            <th class="text-right px-5 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        @foreach($promoCodes as $promo)
                        @php
                            $expired = $promo->valid_until && $promo->valid_until->lt(now());
                            $full = $promo->hasReachedUsageLimit();
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-slate-700/40">
                            <td class="px-5 py-3 font-mono font-medium text-gray-900 dark:text-white">{{ $promo->code }}</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-slate-300">{{ $promo->valueLabel() }}
                                @if($promo->min_amount)
                                    <span class="text-xs text-gray-400 ">(min {{ formatPrice($promo->min_amount) }})</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-600 hidden md:table-cell dark:text-slate-300">{{ $promo->min_amount ? formatPrice($promo->min_amount) : '—' }}</td>
                            <td class="px-5 py-3 text-xs text-gray-600 hidden lg:table-cell dark:text-slate-300">
                                {{ $promo->valid_from ? $promo->valid_from->format('M d, Y') : 'Open' }}
                                &rarr;
                                {{ $promo->valid_until ? $promo->valid_until->format('M d, Y') : '∞' }}
                                @if($expired)
                                    <span class="ml-1 text-red-500 font-medium">(expired)</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $full ? 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400' : 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                    {{ $promo->used_count }}{{ $promo->usage_limit ? ' / '.$promo->usage_limit : '' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($promo->is_active && ! $expired && ! $full)
                                    <svg class="w-5 h-5 text-emerald-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-300 mx-auto dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.promo-codes.edit', $promo) }}" class="p-1.5 text-gray-500 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-teal-300 dark:hover:bg-teal-900/30" aria-label="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.promo-codes.destroy', $promo) }}" @@submit.prevent="$dispatch('open-confirm-delete', { url: '{{ route('admin.promo-codes.destroy', $promo) }}', method: 'DELETE' })">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-red-400 dark:hover:bg-red-500/10" aria-label="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t border-gray-100 dark:border-slate-700">
                <x-admin.pagination :paginator="$promoCodes" />
            </div>
        @endif
    </div>
</div>

<x-admin.confirm-dialog name="delete" title="Delete Promo Code?" message="Are you sure? This cannot be undone." />
@endsection