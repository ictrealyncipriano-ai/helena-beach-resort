@extends('admin.layouts.app')
@section('title', 'Reports & Exports')
@section('header', 'Reports & Exports')
@section('description', 'Download CSV exports of your bookings, revenue, and guests.')

@section('content')
<div class="space-y-6">

    {{-- Inquiries --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 dark:bg-slate-800 dark:border-slate-700">
        <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-1 dark:text-white">
            <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
            Inquiries
        </h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mb-4">All inquiries (source, type, status, amounts, payment).</p>
        <form action="{{ route('admin.exports.inquiries') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="inquiries-from" class="block text-xs font-medium text-gray-600 dark:text-slate-300 mb-1">From</label>
                <input type="date" id="inquiries-from" name="from" value="{{ request('from') }}" class="px-3 py-2 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white rounded-lg text-sm">
            </div>
            <div>
                <label for="inquiries-to" class="block text-xs font-medium text-gray-600 dark:text-slate-300 mb-1">To</label>
                <input type="date" id="inquiries-to" name="to" value="{{ request('to') }}" class="px-3 py-2 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white rounded-lg text-sm">
            </div>
            <div>
                <label for="inquiries-status" class="block text-xs font-medium text-gray-600 dark:text-slate-300 mb-1">Status</label>
                <select id="inquiries-status" name="status" class="px-3 py-2 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white rounded-lg text-sm">
                    <option value="">All</option>
                    @foreach(['pending', 'confirmed', 'cancelled', 'expired'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-teal-700 hover:bg-teal-800 text-white text-sm font-medium rounded-lg transition-colors">Download CSV</button>
        </form>
    </div>

    {{-- Revenue --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 dark:bg-slate-800 dark:border-slate-700">
        <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-1 dark:text-white">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            Revenue
        </h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mb-4">Paid revenue grouped by month and cottage.</p>
        <form action="{{ route('admin.exports.revenue') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="revenue-from" class="block text-xs font-medium text-gray-600 dark:text-slate-300 mb-1">From</label>
                <input type="date" id="revenue-from" name="from" value="{{ request('from') }}" class="px-3 py-2 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white rounded-lg text-sm">
            </div>
            <div>
                <label for="revenue-to" class="block text-xs font-medium text-gray-600 dark:text-slate-300 mb-1">To</label>
                <input type="date" id="revenue-to" name="to" value="{{ request('to') }}" class="px-3 py-2 border border-gray-300 dark:bg-slate-800 dark:border-slate-600 dark:text-white rounded-lg text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-medium rounded-lg transition-colors">Download CSV</button>
        </form>
    </div>

    {{-- Guests --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 dark:bg-slate-800 dark:border-slate-700">
        <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2 mb-1 dark:text-white">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
            Guests
        </h2>
        <p class="text-xs text-gray-500 dark:text-slate-400 mb-4">All guest profiles with lifetime booking and payment stats.</p>
        <a href="{{ route('admin.exports.guests') }}" class="inline-flex px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors">Download CSV</a>
    </div>
</div>
@endsection